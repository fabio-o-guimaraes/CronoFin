/* ============================================
   Comportamento comum a todas as páginas
   ============================================ */

document.addEventListener("DOMContentLoaded", function () {
  const menuToggle = document.querySelector(".menu-toggle");
  const mainMenu = document.querySelector(".main-menu");

  // Se o botão ou o menu não existirem nesta página, sai da função
  // para não bloquear o resto do código com um erro
  if (!menuToggle || !mainMenu) {
    return;
  }

  // Abrir/fechar o menu ao clicar no botão hamburguer
  menuToggle.addEventListener("click", function (event) {
    event.stopPropagation(); // impede que este clique seja também interpretado como clique fora

    const isOpen = mainMenu.classList.toggle("is-open");
    menuToggle.setAttribute("aria-expanded", isOpen);
  });

  // Fechar o menu ao clicar fora dele
  document.addEventListener("click", function (event) {
    const menuIsOpen = mainMenu.classList.contains("is-open");
    const clickedInsideMenu = mainMenu.contains(event.target);

    if (menuIsOpen && !clickedInsideMenu) {
      mainMenu.classList.remove("is-open");
      menuToggle.setAttribute("aria-expanded", "false");
    }
  });
});
