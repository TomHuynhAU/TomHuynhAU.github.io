document.addEventListener("DOMContentLoaded", function () {
  // Bật ngăn cuộn ngay khi DOM sẵn sàng
  document.body.style.overflow = "hidden";
  // Tắt ngăn cuộn khi hoàn tất loading
  setTimeout(() => {
    document.body.style.overflowY = "auto";
  }, 7000); // Tùy thời gian loading
});

window.addEventListener("load", function () {
  const loader = document.getElementById("loading");
  disableScroll();
  setTimeout(() => {
    loader.style.display = "none";
    enableScroll();
  }, 6000); // 3000ms = 3 giây
});

function disableScroll() {
  window.scrollTo(0, 0); // Khóa vị trí cuộn
  document.body.style.overflowY = "hidden"; // Tắt cuộn dọc
  document.body.style.overflowX = "hidden"; // Tắt cuộn ngang
}

function enableScroll() {
  document.body.style.overflowY = "auto"; // Bật cuộn dọc
  document.body.style.overflowX = "hidden"; // Đảm bảo vẫn tắt cuộn ngang
}
