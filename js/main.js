$(function () {

    $.post("php/engine.php", {reset:""});

    var tileCount = 0;
    var colorLength = 0;
    var interval = 10000;
    var color = ['#f0a30a', '#825a2c', '#0050ef', '#a20025', '#1ba1e2', '#d80073', '#a4c400', '#6a00ff', '#60a917', '#008a00', '#76608a', '#6d8764', '#fa6800', '#f472d0', '#e51400', '#7a3b3f', '#647687', '#00aba9', '#aa00ff', '#d8c100'];
    var tileH = 315;
    var tileW = 315;
    var start = true;
    var age = 0;
    var cache = {};

    var horCount = Math.floor($(window).width() / tileW); //gets max number of tiles that can fit on screen horizontal
    var vertCount = Math.floor($(window).height() / tileH); //gets max number of tiles that can fit on screen vertical
    var tileCount = horCount * vertCount;
    
    function getSettings(callback) {

        $.getJSON('php/settings.php', function (settings) {

            settings['color'] != '' ? color = settings['color'] : null; //if custom option not set then set to default
            settings['interval'] != '' ? interval = parseInt(settings['interval']) : null; //if custom option not set then set to default
            colorLength = color.length;

            callback();
        });
    }
    
	function fixImage(elem) {

		var pic = elem.find('.pic img');
		
		if (pic.length != 0) {

			var margin = 0;
			
			var m = '';

			if ((pic.width() / 275) < (pic.height() / 220)) {
				pic.width(275);
				margin = (pic.height() - 220) / 2;
				m = 'margin-top';
			} else {
				pic.height(220);
				margin = (pic.width() - 275) / 2;
				m = 'margin-left';
			}
			
			if (margin < 0) {
				
				margin = Math.abs(margin);
				
			} else {
				
				margin = -Math.abs(margin);
				
			}
			
			pic.css(m, margin + 'px');
		}
	}
	
    function getEmptyTiles() { //get list of empty tiles

        var empty = [];

        $('.tile').each(function () {

            if ($(':first-child', this).length === 0) {
                empty.push($(this).attr('id'));

            }
        });

        return empty;

    }
    
    function getContrast50(hexcolor){
		return (parseInt(hexcolor.replace('#', ''), 16) > 0x808080/2) ? 'dark':'light';
	}
      
    function hexToRgb(hex) {
		var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
		return result ? {
			r: parseInt(result[1], 16),
			g: parseInt(result[2], 16),
			b: parseInt(result[3], 16)
		} : null;
	}

	function getContrast(hex){
		
		var rgb = hexToRgb(hex);
			
		var o = Math.round(((parseInt(rgb['r']) * 299) + (parseInt(rgb['g']) * 587) + (parseInt(rgb['b']) * 114)) / 1000);

		if (o >= 128) {
			return '#000000';
		} else {
			return '#ffffff';
		}            
	}
	
    function getTileAges() { //get list of empty tiles

        var ages = [];

        $('.tile').each(function () {
            ages.push(parseInt($(this).attr('age')));
        });
        
        return ages;

    }

    function generateTiles() {

        for (var i = 0; i < horCount * vertCount; i++) { //generate tiles with random colors
			
			var hex = color[Math.floor(Math.random() * (colorLength))];
            $('#tiles').append('<div id="' + i + '" tweetid="" age="" class="tile"></div>');
            $('#' + i).css('background-color', hex);
            $('#' + i).css('color', getContrast(hex));

        }

        var height = (($(window).height() - (Math.floor($(window).height() / tileH) * tileH)) / 2) + 20;
        var width = (($(window).width() - (Math.floor($(window).width() / tileW) * tileW)) / 2) + 20;

        $('body').css('padding-top', height); //centers tiles in window
        $('body').css('padding-left', width);

    }

    function getRandID(empty) { //gets random id of tile to append tweet

        if (empty.length != 0) { //use empty tile for new tweet

            return empty[Math.floor(Math.random() * empty.length)];

        } else { //all tiles full then choose random tile

            return Math.floor(Math.random() * (tileCount));

        }

    }

    function emoji(text) {

        text = jEmoji.softbankToUnified(text);
        text = jEmoji.googleToUnified(text);
        text = jEmoji.docomoToUnified(text);
        text = jEmoji.kddiToUnified(text);
        return jEmoji.unifiedToHTML(text);
    }
	
    function ajax() {

		var empty = getEmptyTiles();
		var ages = getTileAges();
		
        $.getJSON('php/engine.php', function (tweets) { //get json

            tweets = tweets.slice(0 , tileCount);
            
            if (tweets.length != 0) { //if no new tweets do nothing

                for (var i = 0; i < tweets.length; i++) { //loop through tweets in json

                    (function (real_i) { //true count is lost due to anonymous function closure

						var content = '<span class="text">' + emoji(tweets[real_i]['text']) + '</span>';
                        
						if (tweets[real_i]['media_url'] != '') { //if pic exists
						
							content = '<span class="pic">' + tweets[real_i]['media_url'] + '</span>';
							
						}
						
						if (start) {//first tile iteration
							var randID = getRandID(empty);
							
							empty = $.grep(empty, function(value) {//remove used tile from empty array
								return value != randID;
							});
							
						} else {//remove old tweet oldest to newest
						
							randID = $('.tile[age="' + Math.min.apply(null, ages) + '"]').attr('id');//get oldest tweet id
							ages = $.grep(ages, function(value) {//remove used age from empty array
								return value != randID;
							});
							
						}
						
						$('#' + randID).attr('age', age);//add age to tile
						age++;
						
                        $('#' + randID).animate({
                            opacity: 0
                        }, 2000, function () { //fadeout old tile
                        
							var c = '<p><span class="profile_image">' + tweets[real_i]['profile_image_url'] + '</span>' +
									'<span class="name">' + tweets[real_i]['name'] + '</span>' +
									'<span class="mention">' + tweets[real_i]['screen_name'] + '</span></p>' +
									'<p>' + content + '</p>';
									
							if (cache[randID] === undefined) {
								cache[randID] = [];
							}
							cache[randID][cache[randID].length] = c;//cache tile content
                        
                            $(this).attr('tweetid', tweets[real_i]['id_str']).css('background-color', color[Math.floor(Math.random() * (colorLength))]).html( //change bg color and fadein tile
								c).animate({
                                opacity: 1
                            }, 2000);
							fixImage($(this));
                        });
                      
                    }(i));
                }
				start = false;
				
            } else { //if no new tweets then cycle random tile

                for (var i = 0; i < 3; i++) {
					
                    $('#' + Math.floor(Math.random() * (tileCount))).animate({
                        opacity: 0
                    }, 2000, function () {
						var hex = color[Math.floor(Math.random() * (colorLength))];
						$(this).html(cache[$(this).attr('id')][Math.floor(Math.random() * (cache[$(this).attr('id')].length))]);
                        $(this).css('background-color', hex).animate({
                            opacity: 1
                        }, 2000);
                        $(this).css('color', getContrast(hex));
						fixImage($(this));
                    });

                }

            }
        });
    }

    getSettings(function () { //callback so we get setting before anything else
        generateTiles(); //generate layout
        ajax(); //get initial tweets
        setInterval(ajax, interval); //check for new tweets every x seconds
    });


    $('body').on('click', '.tile', function() {
		var t = $(this).html();
		var n = $(this).attr('id');
        $(this).empty();
        for (var i = 0; i < cache[n].length; i++) {
			if (cache[n][i] === t) {
				cache[n].splice(i, 1);
			}
		}
    });

});
