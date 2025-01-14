

const lot = document.querySelectorAll("lottie-player");

const src = "http://localhost:8888/themes/dark/animations/addList.json";

//console.log(lot.getAttribute("data-state"));

for(n=0; n<lot.length; n++) {
    lot[n].load(src);
    lot[n].autoplay = false;
    lot[n].loop = false;
}

lot.forEach((item) => {
    dataState = item.getAttribute("data-state");
    console.log(dataState);
    if(dataState=="0") {
        console.log("La peli NO esta en la lista");
    } else {
        console.log("La peli SI esta en la lista");
        item.play();
    }
})
