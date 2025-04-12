document.addEventListener("DOMContentLoaded", () => {
  const navbar = document.querySelector(".navbar");
  const info2 = document.querySelector("#info2");
  const loading = document.getElementById("loading");
  const loadingPercentage = document.getElementById("loading-percentage");
  const loadingProgress = document.querySelector(".loading-progress");

  let progress = 0;

  // Tăng dần phần trăm và chiều rộng thanh loading
  const interval = setInterval(() => {
    progress += 1;
    loadingPercentage.textContent = `${progress}%`;
    loadingProgress.style.width = `${progress}%`;

    // Khi đạt 100%, ẩn màn hình loading
    if (progress >= 100) {
      clearInterval(interval);
      setTimeout(() => {
        loading.classList.add("hidden"); // Thêm lớp hidden để mờ dần
        setTimeout(() => {
          loading.style.display = "none"; // Ẩn hoàn toàn sau khi mờ dần
        }, 500); // Thời gian khớp với transition trong CSS
      }, 500); // Thêm 0.5 giây để hoàn tất hiệu ứng
    }
  }, 30); // Cập nhật mỗi 30ms

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
