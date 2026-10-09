document.querySelectorAll('[data-product-image]').forEach((image) => {
    const showFallback = () => image.remove();
    const showImage = () => image.classList.remove('opacity-0');
    image.addEventListener('error', showFallback, { once: true });
    image.addEventListener('load', showImage, { once: true });
    if (image.complete && image.naturalWidth === 0) showFallback();
    else if (image.complete) showImage();
});
