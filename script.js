document.addEventListener("DOMContentLoaded", () => {
  const navbar = document.querySelector(".navbar");
  const info2 = document.querySelector("#info2");
  const background = document.getElementById("background");
  const img = new Image();
  img.src = "./assets/background.jpg"; // Đường dẫn ảnh nền
  img.onload = () => {
    background.style.backgroundImage = `url(${img.src})`;
  };

  window.addEventListener("scroll", () => {
    if (!info2) return;

    const info2Top = info2.offsetTop;

    if (window.scrollY >= info2Top - navbar.offsetHeight) {
      navbar.classList.add("shadow");
    } else {
      navbar.classList.remove("shadow");
    }
  });
});
