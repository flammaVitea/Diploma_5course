// script.js
document.addEventListener('DOMContentLoaded', function () {
    const burger = document.getElementById('burger');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');

    burger.addEventListener('click', function () {
        sidebar.classList.toggle('hidden');
        content.classList.toggle('full-width');
    });
});
