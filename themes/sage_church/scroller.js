//scrolling javascript with scroll amount specific to each theme
//simply scrolls down to picture so you don't have to scroll down past
//the header every time you click next or prev

function galleryScroll() {
	scroll(1, 220);
}

function setScrollPos(formName) {
	if(document.all) {
		mytop = document.body.scrollTop;
		myleft = document.body.scrollLeft;
	} else {
        myleft = window.pageXOffset;
        mytop= window.pageYOffset;
    }

	document.forms[0].scrollPos.value = mytop;
}

function scrollIt() {
		scroll(0, document.forms[0].scrollPos.value);
}
