window.addEventListener("load", function () {
  const loader = document.getElementById("loading");
  setTimeout(() => {
    loader.style.display = "none";
  }, 6000); // 3000ms = 3 giây
});
