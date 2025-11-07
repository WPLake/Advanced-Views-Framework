let itemsWrapper = this.querySelector('.top-header__items-wrapper');
let _this = this;

function toggle() {
    itemsWrapper.style.maxHeight = itemsWrapper.scrollHeight + 'px';
    _this.classList.toggle('top-header--open');
}

function init() {
    _this.querySelectorAll('.top-header__menu-icon').forEach(menuIcon => {
        menuIcon.addEventListener('click', (event) => {
            event.preventDefault();

            toggle();
        });
    });
}

init();