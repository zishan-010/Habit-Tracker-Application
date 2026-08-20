// List your images here
// const images = [
//     "/root/resources/images/image1.png",
//     "root/resources/images/image2.png",
//     "root/resources/images/image3.png"
// ];
const basePath = window.location.origin + "/resources/images/";
const images = [
    basePath + "image.png",
    basePath + "image.png",
    basePath + "image.png"
];

// ...rest identical to Method 1

const group = document.getElementById("slideGroup");
const groupClone = document.getElementById("slideGroupClone");

// Build the two groups (original + clone for seamless looping)
images.forEach(src => {
    const img = document.createElement("img");
    img.src = src;
    img.className = "card";
    group.appendChild(img);

    const imgClone = img.cloneNode();
    groupClone.appendChild(imgClone);
});

// JS-driven animation loop
const slideshow = document.getElementById("slideshow");
let position = 0;
const speed = 0.6; // pixels per frame, tweak for speed

function animate() {
    position -= speed;

    // Reset once the first group has fully scrolled out
    if (Math.abs(position) >= group.scrollWidth) {
        position = 0;
    }

    group.style.transform = `translateX(${position}px)`;
    groupClone.style.transform = `translateX(${position}px)`;

    requestAnimationFrame(animate);
}

requestAnimationFrame(animate);