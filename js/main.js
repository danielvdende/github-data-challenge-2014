// global variables for language name representation.
var globalLanguageIndexConverter = {
  "javascript" : 0,
  "ruby" : 1,
  "java" : 2,
  "php" : 3,
  "python" : 4,
  "c++" : 5,
  "c" : 6,
  "objective-c" : 7,
  "c#" : 8,
  "shell" : 9,
  "css" : 10,
  "perl" : 11,
  "coffeescript" : 12,
  "viml" : 13,
  "scala" : 14,
  "go" : 15,
  "prolog" : 16,
  "clojure" : 17,
  "haskell" : 18,
  "lua" : 19
};
var globalFormattedLanguageIndices = {
    "JavaScript" : 0,
    "Ruby" : 1,
    "Java" : 2,
    "PHP" : 3,
    "Python" : 4,
    "C++" : 5,
    "C" : 6,
    "Objective-C" : 7,
    "C#" : 8,
    "Shell" : 9,
    "CSS" : 10,
    "Perl" : 11,
    "CoffeeScript" : 12,
    "VimL" : 13,
    "Scala" : 14,
    "Go" : 15,
    "Prolog" : 16,
    "Clojure" : 17,
    "Haskell" : 18,
    "Lua" : 19
}
var totalSum = 0;

initialize();

/**
 * Initialize visualization 1, fetching the data using AJAX.
 */
function initialize(){
var request = new XMLHttpRequest();
    var data = {
        "callType": "init"
    };
    request.open('POST', 'backend/main.php', true);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    request.send("data=" + JSON.stringify(data));
    request.onload = function() {
        if (request.status >= 200 && request.status < 400){
            data = JSON.parse(request.responseText);
            // parse the result and draw the chord diagram.
            parseMatrix(data.finals, data.totals);
            // hide the loading gif
            document.getElementById("vis3Loader").style.display="none";
        } else {
            // We reached our target server, but it returned an error
            console.log("Oh noesz");
        }
    };
}

/**
 * Parse the results of fetching the data for the chord diagram.
 * @param  {Array} finals Array of Objects containing the information between all languages.
 * @param  {Array} totals Array of Object, containing the information for the totals per language.
 */
function parseMatrix(finals,totals){
    // First parse the matrix to the required form for the chord diagram.
    var totalKeys = Object.keys(totals);
    totalSum = 0;

    for (var i = totalKeys.length - 1; i >= 0; i--) {
        totalSum += parseInt(totals[totalKeys[i]]);
    };
    var data = finals;
    var keys = Object.keys(data);
    var matrix = [];
    var lang;
    var langKeys;
    for(var i=0; i < keys.length; i++){
        lang = [];
        langKeys = Object.keys(data[keys[i]]);
        for(var j=0; j < langKeys.length; j++){
            lang[globalLanguageIndexConverter[langKeys[j]]] = parseInt(data[keys[i]][langKeys[j]]);
        }
        matrix.push(lang);
    }

    // start drawing the chord diagram using D3.
    var chord = d3.layout.chord()
        .padding(.05)
        .sortSubgroups(d3.descending)
        .matrix(matrix);

    // Constants used for the chord diagram.
    var width = 1000,
        height = 1100,
        innerRadius = Math.min(width, height) * .41,
        outerRadius = innerRadius * 1.1;
        r1 = innerRadius;
        r0 = outerRadius;

    // category20 works perfectly here, using 20 languages :)
    var fill = d3.scale.category20();

    // create the SVG element.
    var svg = d3.select("#vis3").append("svg")
        .attr("width", width)
        .attr("height", height)
        .append("g")
        .attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");

    // create an arc object.
    var arc = d3.svg.arc();

    svg.append("g").selectAll("path")
        .data(chord.groups)
        .enter().append("path")
        .style("fill", function(d) { return fill(d.index); })
        .style("stroke", function(d) { return fill(d.index); })
        .attr("d", arc.innerRadius(innerRadius).outerRadius(outerRadius))
        .on("mouseover", fade(.1))
        .on("mouseout", fade(1));

    // chords!
    svg.append("g")
        .attr("class", "chord")
        .selectAll("path")
        .data(chord.chords)
        .enter().append("path")
        .attr("d", d3.svg.chord().radius(innerRadius))
        .style("fill", function(d) { return fill(d.target.index); })
        .style("opacity", 1)
        .on("mouseover", function(d) {
            d3.select("#tooltip")
            .style("visibility", "visible")
            .html(getTooltip(d, totalSum))
            .style("top", function () { return (d3.event.pageY - 50)+"px"})
            .style("left", function () { return (d3.event.pageX - 100)+"px";})
            fade(0.1);
        })
        .on("mouseout", function(){
            d3.select("#tooltip").style("visibility", "hidden");
            fade(1);
    });


    // language groups
    var g = svg.selectAll("g.group")
        .data(chord.groups)
        .enter().append("svg:g")
        .attr("class", "group")
        .on("mouseover", fade(.02))
        .on("mouseout", fade(.80));

    g.append("svg:path")
        .style("stroke", function(d) { return fill(d.index); })
        .style("fill", function(d) { return fill(d.index); })
        .attr("d", arc);

    g.append("svg:text")
        .each(function(d) { d.angle = (d.startAngle + d.endAngle) / 2; })
        .attr("dy", ".35em")
        .attr("text-anchor", function(d) { return d.angle > Math.PI ? "end" : null; })
        .attr("transform", function(d) {
            return "rotate(" + (d.angle * 180 / Math.PI - 90) + ")"
                + "translate(" + (r0 + 26) + ")"
                + (d.angle > Math.PI ? "rotate(180)" : "");
        })
        .text(function(d) { 
            return getFormattedLanguageString(d.index);// + "(" + perc + "%)"; 
        });


    // Returns an event handler for fading a given chord group.
    function fade(opacity) {
        return function(g, i) {
            svg.selectAll(".chord path")
                .filter(function(d) { return d.source.index != i && d.target.index != i; })
                .transition()
            .style("opacity", opacity);
        };
    }
}

/**
 * Returns the language string associated with an index
 * @param  {Int} index Index of the desired language
 * @return {String}       Language Name
 */
function getLanguageString(index){
    var keys =  Object.keys(globalLanguageIndexConverter);
    return keys[index];
}

/**
 * Returns the formatted language string for a given index
 * @param  {Int} index Index of the desired language
 * @return {String}       Formatted language string.
 */
function getFormattedLanguageString(index){
    var keys =  Object.keys(globalFormattedLanguageIndices);
    return keys[index];
}

/**
 * Get a formatted tooltip string to show on mouseover.
 * @param  {Chord Object} chord    The chord object that has been 'moused over'
 * @param  {Int} totalSum Total sum of the language in question (used for the percentage shown in the tooltip)
 * @return {String}          The tooltip string that will be shown.
 */
function getTooltip(chord, totalSum){
    var string = getFormattedLanguageString(chord.source.index) + " - " + getFormattedLanguageString(chord.source.subindex) + "<br />";
    string += "Users: " + chord.source.value + "(" + ((chord.source.value / totalSum) * 100 ).toFixed(2) + "%)";
    return string;
}

/**
 * Handle the username search (i.e. the usercard)
 */
document.getElementById("usernameSearch").addEventListener("click", function(){
    var request = new XMLHttpRequest();
    var data = {
        "callType": "usernameSearch",
        "username": document.getElementById("usernameField").value
    };
    request.open('POST', 'backend/main.php', true);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    request.send("data=" + JSON.stringify(data));
    request.onload = function() {
        if (request.status >= 200 && request.status < 400){
            var data = JSON.parse(request.responseText);
            var elements = document.getElementsByClassName("cardSec");
            // reset the colour for all languages initially.
            for (var i = elements.length - 1; i >= 0; i--) {
                elements[i].style.opacity = 0.1;
                elements[i].style.color = "#000";
            };
            // colour the languages the user 'speaks'
            for (var i = data.length - 1; i >= 0; i--) {
                document.getElementById(data[i] + "Sec").style.opacity = 1;
                document.getElementById(data[i] + "Sec").style.color = "#2ecc71";

            };
        } else {
            // We reached our target server, but it returned an error
            console.log("Oh noesz");
        }
    }
});

/**
 * Handler for the language searching (i.e. finding users that speak a language)
 */
document.getElementById("languageSearch").addEventListener("click", function(){
    var checkList = document.getElementsByClassName("langSec");
    var languages = [];
    for(var i=0; i < checkList.length; i++){
        if(checkList[i].classList.contains("langSelected")) {
            languages.push(checkList[i].id.replace("Lang", ""));
        }
    }
    var data = {
        "callType": "languageSearchLimited",
        "languages": languages
    };
    var request = new XMLHttpRequest();
    request.open("POST", "backend/main.php", true);
    request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
    var test = JSON.stringify(data);
    request.send("data=" + JSON.stringify(data));
    request.onload = function () {
        if(request.status >= 200 && request.status < 400) {
            var data = JSON.parse(request.responseText);
            // draw the pie chart and the table.
            drawUserLanguageTable(data);
            drawUserLanguagePieChart(data);
        } else {
            console.log("Oh noesz");
        }
    }
});

/**
 * Draw the list of users that speak the given set of languages
 * @param  {Array} data Array containing data about the totals that speak this selection of languages and the list of users itself.
 */
function drawUserLanguageTable(data){
    var table = document.getElementById("languageSearchResultsBody");
    table.innerHTML = "";
    var fragment = document.createDocumentFragment();
    var total = data.total;
    var data = data.users;
    for(var i=0; i < data.length; i++){
        var tr = document.createElement("tr");
        var td = document.createElement("td");
        td.innerHTML = "<a href='https://github.com/" + data[i] + "' >" + data[i] + "</a>";
        tr.appendChild(td);
        fragment.appendChild(tr);
    }
    table.appendChild(fragment);
    document.getElementById("languageSearchResults").style.display = "block";
    if(total > data.length){
      // apparently this isn't the total list 
      document.getElementById("fetchMoreButton").style.display="block";
    }
}

/**
 * Draw the pie chart that indicates how many people speak the user's selection.
 * @param  {Array} data Array of objects containing information on the users
 */
function drawUserLanguagePieChart(data){
    document.getElementById("languageUserSearch").innerHTML = "";
    var pieData = [
        {
            "name" : "Selection",
            "number": parseInt(data.total),
            "colorIndex": 0
        },
        {
            "name" : "Other",
            "number": totalSum, // TODO: fix this hardcoded value. This value also needs checking, seems to be incorrect.   
            "colorIndex": 1
        }
    ];
    var width = 500,
        height = 300,
        radius = Math.min(width, height) / 2;

    var color = d3.scale.ordinal()
        .range(["#2ecc71", "#e74c3c"]);

    var arc = d3.svg.arc()
        .outerRadius(radius - 10)
        .innerRadius(0);

    var pie = d3.layout.pie()
        .sort(null)
        .value(function(d) { return d.number; });

    var svg = d3.select("#languageUserSearch").append("svg")
        .attr("width", width)
        .attr("height", height)
      .append("g")
        .attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");


    pieData.forEach(function(d) {
        d.number = +d.number;
    });

    var g = svg.selectAll(".pie_arc")
        .data(pie(pieData))
    .enter().append("g")
        .attr("class", "pie_arc");

    g.append("path")
        .attr("d", arc)
        .style("fill", function(d) {return color(d.data.colorIndex); })
        .style("stroke", function() {return "#fff";})
        .on("mouseover", function(){
            d3.select(this)
                .style("stroke", function() {return "#000";});
        })
        .on("mouseout", function(){
            d3.select(this)
                .style("stroke", function(){return "none";})
        });

    g.append("text")
        .attr("transform", function(d) { return "translate(" + arc.centroid(d) + ")"; })
        .attr("dy", ".35em")
        .style("text-anchor", "middle")
        .text(function(d) { return d.data.name; });
}

var langList = document.getElementsByClassName("langSec");
for (var i = langList.length - 1; i >= 0; i--) {
  langList[i].addEventListener("click", function(){
    toggleLangSelection(this);
  });
};

/**
 * Helper function that allows easy class manipulation of a clicked element.
 * @param  {DOM Element} element The DOM element that was clicked
 */
function toggleLangSelection(element){
    if(element.classList.contains("langSelected")){
        element.classList.remove("langSelected");
    } else {
        element.classList.add("langSelected");
    }
}

/**
 * Handler for the fetch more button (i.e. if a user wants to see more than the first 10 results).
 */
document.getElementById("fetchMoreButton").addEventListener("click", function(){
  var checkList = document.getElementsByClassName("langSec");
  var languages = [];
  for(var i=0; i < checkList.length; i++){
      if(checkList[i].classList.contains("langSelected")) {
          languages.push(checkList[i].id.replace("Lang", ""));
      }
  }
  var data = {
      "callType": "languageSearch",
      "languages": languages
  };
  var request = new XMLHttpRequest();
  request.open("POST", "backend/main.php", true);
  request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
  var test = JSON.stringify(data);
  document.getElementById("languageLoader").style.display = "block";
  request.send("data=" + JSON.stringify(data));
  request.onload = function () {
      if(request.status >= 200 && request.status < 400) {
          var data = JSON.parse(request.responseText);
          drawUserLanguageTable(data);
          drawUserLanguagePieChart(data);
          document.getElementById("languageLoader").style.display = "none";
      } else {
          console.log("Oh noesz");
      }
  }
});
