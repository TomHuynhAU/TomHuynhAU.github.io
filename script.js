window.addEventListener("load", function () {
  const loader = document.getElementById("loading");
  setTimeout(() => {
    loader.style.display = "none";
  }, 3000); // 3000ms = 3 giây
});
