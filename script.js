document.addEventListener("DOMContentLoaded", function () {
  // Ẩn cuộn ngay khi DOM sẵn sàng
  document.body.style.overflow = "hidden";

  // Tắt ngăn cuộn khi hoàn tất loading
  setTimeout(() => {
    document.body.style.overflowY = "auto";
  }, 7000); // Thời gian loading

  const titleElements = document.querySelectorAll(".title");
  const slideUpElements = document.querySelectorAll(".slide-up");

  // Hiệu ứng xuất hiện của chữ GTA STREET
  titleElements.forEach((element1) => {
    setTimeout(() => {
      element1.classList.add("show");

      // Khi hiệu ứng của chữ GTA STREET hoàn tất, hiển thị .slide-up
      setTimeout(() => {
        slideUpElements.forEach((element) => {
          element.classList.add("show");
        });
      }, 500); // Delay 2 giây sau khi chữ GTA STREET hiện xong
    }, 6500); // Delay trước khi hiệu ứng GTA STREET bắt đầu
  });
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
