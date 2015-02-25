$(function () {
    var hnav = document.getElementById('header-nav');
    $(hnav).bind('scroll', dropSidemenuShadow);
    $(window).bind('resize', function(){
        dropSidemenuShadow.apply(hnav);
    });
    dropSidemenuShadow.apply(hnav);
});

function dropSidemenuShadow() {
    var $hsec = $("#header-security");
    if(this.scrollHeight === this.clientHeight) {
        $hsec.removeClass('drop-shadow');
        return;
    }
    var scrollPurcent = 100 * this.scrollTop / this.scrollHeight / (1 - this.clientHeight / this.scrollHeight);
    isNaN(scrollPurcent) || scrollPurcent >= 100 ? $hsec.removeClass('drop-shadow') : $hsec.addClass('drop-shadow');
}
