<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('module-products')]
class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_stores_image_locally_and_renders_its_public_url(): void
    {
        $this->actingAs(User::factory()->create());

        Storage::fake('public');
        $image = UploadedFile::fake()->image('rice.png');

        $this->post(route('products.store'), [
            'name' => 'Rice', 'price' => 8, 'stock' => 5, 'image' => $image,
        ])->assertRedirect();

        $product = Product::where('name', 'Rice')->firstOrFail();
        $path = $product->image;
        $this->assertNotNull($path);
        $this->assertMatchesRegularExpression('/\Aproducts\/[a-zA-Z0-9]{40}\.png\z/', $path);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'image' => $path]);
        Storage::disk('public')->assertExists($path);
        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('/storage/'.$path);
        $this->get(route('products.index'))->assertSee('/storage/'.$path);
    }

    public function test_edit_without_upload_keeps_existing_image_and_replacement_removes_old_file(): void
    {
        $this->actingAs(User::factory()->create());

        Storage::fake('public');
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('products', 'public');
        $product = Product::create(['name' => 'Rice', 'price' => 8, 'stock' => 5, 'image' => $oldPath]);

        $this->put(route('products.update', $product), ['name' => 'Local Rice', 'image' => null])
            ->assertRedirect();
        $this->assertSame($oldPath, $product->fresh()->image);
        Storage::disk('public')->assertExists($oldPath);

        $image = UploadedFile::fake()->image('replacement.png');
        $this->put(route('products.update', $product), ['image' => $image])->assertRedirect();
        $newPath = $product->fresh()->image;
        $this->assertNotNull($newPath);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'image' => $newPath]);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_invalid_and_oversized_uploads_do_not_change_existing_image(): void
    {
        $this->actingAs(User::factory()->create());

        Storage::fake('public');
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('products', 'public');
        $product = Product::create(['name' => 'Rice', 'price' => 8, 'stock' => 5, 'image' => $oldPath]);

        foreach ([
            UploadedFile::fake()->create('script.php', 1, 'text/plain'),
            UploadedFile::fake()->image('large.jpg')->size(2049),
        ] as $image) {
            $this->putJson(route('products.update', $product), ['image' => $image])
                ->assertUnprocessable()->assertJsonValidationErrors('image');
        }

        $this->assertSame($oldPath, $product->fresh()->image);
        Storage::disk('public')->assertExists($oldPath);
        Storage::disk('public')->assertCount('products', 1);
    }

    public function test_soft_deleting_products_preserves_their_uploaded_image(): void
    {
        $this->actingAs(User::factory()->create());

        Storage::fake('public');
        $path = UploadedFile::fake()->image('rice.jpg')->store('products', 'public');
        $first = Product::create(['name' => 'Rice', 'price' => 8, 'stock' => 5, 'image' => $path]);
        $second = Product::create(['name' => 'Rice bag', 'price' => 12, 'stock' => 5, 'image' => $path]);

        $this->delete(route('products.destroy', $first))->assertRedirect();
        Storage::disk('public')->assertExists($path);
        $this->delete(route('products.destroy', $second))->assertRedirect();
        Storage::disk('public')->assertExists($path);
        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
    }

    public function test_legacy_image_files_are_not_deleted_when_replacing_an_image(): void
    {
        $this->actingAs(User::factory()->create());

        Storage::fake('public');
        Storage::disk('public')->put('shirts/white-tee.jpg', 'existing image');
        $product = Product::create(['name' => 'Tee', 'price' => 8, 'stock' => 5, 'image' => 'shirts/white-tee.jpg']);

        $this->put(route('products.update', $product), ['image' => UploadedFile::fake()->image('tee.jpg')])
            ->assertRedirect();
        Storage::disk('public')->assertExists('shirts/white-tee.jpg');
    }

    public function test_missing_legacy_image_uses_the_product_fallback_without_requesting_a_broken_url(): void
    {
        $product = Product::query()->create([
            'name' => 'Rice',
            'price' => 8,
            'stock' => 5,
            'image' => 'missing/rice.jpg',
        ]);

        $this->assertNull($product->imageUrl());
        $this->get(route('products.index'))
            ->assertOk()
            ->assertDontSee('/missing/rice.jpg');
    }
}
