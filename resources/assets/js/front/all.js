import 'bootstrap/js/dist/dropdown'
window.onscroll = function () {
  var nav = document.getElementById('menuTop');
  if (window.pageYOffset > 30) {
    nav.classList.add("scrolled");
  } else {
    nav.classList.remove("scrolled");
  }
}
