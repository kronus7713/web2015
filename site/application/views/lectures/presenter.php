<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
Permissions::require_logged_in ();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1,maximum-scale=1" />
<title>Markdown Presenter</title>
<style>
  html, body {
  	margin:0;
  	border:0;
  	padding:0;
  	font-family: helvetica;
    color: white;
    overflow: hidden;
    -ms-touch-action: none;
    -moz-user-select:none;
  }
  div.centered {
  	margin:auto;
  	font-size:40px;
  	width:25em; /* So we get about 10 words per line */
  }
  .slideCount {
    position: fixed;
    bottom: 1em;
    right: 2px;
    height:1.5em;
    width:8em;
    overflow:hidden;
  }
   .slideProgress
  {
    background-color: green;
    width: 100%;
    min-height: 10px;
  }
  .slideCount select {
    color:white;
    background-color:#112;
    border-color:#112;
    border:none;
    font-size:1em;
    height:1.3em;
    width:10em;

    -webkit-appearance: none;
    border-radius:0;
    -webkit-border-radius: 0;
  }
  .padding {
    height: 60px;
  }

  /* My styling here */
  body,
  .content-holder {
    background: #223;
    text-shadow: #000 0 -2px 0;
  }

   code {
       color: rgb(235, 174, 108);
  }
   .for-print .content-holder {
        width: 100%;
        height: 100%;
        border-collapse: collapse;
   }
   .for-print .content-holder.template { display:none; }
   @media screen {
       .for-print { display:none; }
   }
   @media print {
       .for-screen { display: none; }
   }
   @media only screen and (max-width: 768px) {
       .centered {
           -ms-zoom: 0.8;
           zoom: 0.8;
       }
   }
   @media only screen and (max-width: 480px) {
       .centered {
           -ms-zoom: 0.5;
           zoom: 0.5;
       }
   }
   @media only screen and (max-width: 320px) {
       .centered {
           -ms-zoom: 0.35;
           zoom: 0.35;
       }
   }
 /* End styling */
</style>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
<script>
if (!window.jQuery) {
    document.write('<script src="jquery.min.js"><\/script>');
}
</script>
<script type="text/javascript" src="<?=base_url();?>assets/js/showdown.js"></script>

</head>
<body tabindex="1">
<div class="for-screen">

<div class="bg">&nbsp;</div>

<table style="width:100%;height:100%;border-collapse:collapse">
<tr valign=center>
<td>
<div class='centered'>
<em>Loading</em>
</div>
</td>
</tr>
</table>

<div class="slideCount">
  <select tabindex="-1">
  </select>
  <div class="slideProgress"></div>
</div>

  <div class="padding">
    <!-- This padding need to hiding address bar automatically at iPhone/iPad/Android browser. -->
  </div>

</div>

<div class="for-print">
    <table class="content-holder template">
    <tr valign=center>
    <td>
    <div class='centered'><h2>Foo</h2>
    </div>
    </td>
    </tr>
    </table>
</div>

</body>
</html>

<script>
var Present = {
    // Slide transition effects.
    effects: {
        // n: no effect.
        n: {
            pre: function (view, callback) { callback(); },
            post: function (view) { }
        },
        // f: fade in/out.
        f: {
            pre: function (view, callback) { view.fadeOut('fast', callback); },
            post: function (view) { view.fadeIn('fast'); }
        }
    },
};
Present.currentEffect = Present.effects['n']; // Default slide transiton effect is nothing.

Present.hashToSlideIndex = function () {
    var matches = window.location.hash.match(/#(\d+)/);
    if (matches == null) return 0;
    return parseInt(matches[1]) - 1;
}

Present.currentSlide = Present.hashToSlideIndex();

Present.showSlide = function(slide) {
  slide = Math.max(0, Math.min(slide, Present.slides.length - 1));
  Present.currentSlide = slide;

  var $view = $('.for-screen .centered');
  this.currentEffect.pre($view, $.proxy(function () {
      $view.html(this.slides[this.currentSlide]);
      this.currentEffect.post($view);
  }, this));

  $('.slideCount select').val(this.currentSlide);
  $('.slideProgress').css("width", (this.currentSlide + 1)/this.slides.length * 100 + "%");
  window.location.hash = '#' + (Present.currentSlide + 1);
};
Present.nextSlide = function() {
  if (Present.currentSlide < Present.slides.length-1) {
    Present.showSlide(Present.currentSlide+1);
  }
};
Present.prevSlide = function() {
  if (Present.currentSlide > 0) {
    Present.showSlide(Present.currentSlide-1);
  }
};

Present.buildPrintPage = function () {
    var forPrint = $('.for-print');
    $('.content-holder:not(.template)', forPrint).remove();;
    var template = $('.content-holder.template', forPrint);
    $.each(Present.slides, function (_, slide) {
        var holder = template.clone()
            .appendTo(forPrint)
            .removeClass('template');
        $('.centered', holder).html(slide);
    });
}

Present.buildSlideCounter = function () {
    var $counter = $('.slideCount select');
    var optionHtmls = [];
    var numOfSlides = this.slides.length;
    $.each(this.slides, function (i) {
        optionHtmls.push('<option value="' + i + '">Slide ' + (i + 1) + ' of ' + numOfSlides + '</option>');
    });
    $counter.html(optionHtmls.join(''));
}

Present.reload = function() {
    $.ajax({
        url: '<?php echo $presentation; ?>',
        cache: false,
        dataType: 'text',
        success: function(data) {
            if (data.length>0) {
                converter = new Showdown.converter();
                var converted = converter.makeHtml(data);
                Present.slides = converted.split('<p>!</p>');
                Present.buildSlideCounter();
                Present.showSlide(Present.currentSlide);

                // If beforeprint event not supported, build pages to printing now.
                if (('onbeforeprint' in window) === false) Present.buildPrintPage();
            }
        }
    });
};
Present.reload();

(function () {
    var pageToJump = '';
    var effectCmd = false;

    $(document).keydown(function(e){
        if (e.keyCode == 13) { // enter
            if (pageToJump != '') {
                Present.showSlide(parseInt(pageToJump) - 1);
                pageToJump = '';
            }
        }
        if (48 <= e.keyCode && e.keyCode <= 57) {
            pageToJump += String.fromCharCode(e.keyCode);
        }
        else {
            pageToJump = '';
        }

        // Detect key combination to change slide transition effect. 
        // ex) 'e','f' -> change effect to fade in/out.
        if (e.keyCode == 'E'.charCodeAt(0)) effectCmd = true;
        else if (effectCmd)
        {
            var effectName = String.fromCharCode(e.keyCode).toLowerCase();
            var effect = Present.effects[effectName];
            if (typeof (effect) !== 'undefined') Present.currentEffect = effect;
            effectCmd = false;
        }

        if (e.keyCode == 37) {
            Present.prevSlide();
            return false;
        }
        if (e.keyCode == 39) {
            Present.nextSlide();
            return false;
        }
        if (e.keyCode == 32) { // space
            Present.reload();
            return false;
        }
    });
})();

// Build print page on demand.
$(window).bind('beforeprint', Present.buildPrintPage);

$(function () {
    var startPos = null;
    var isPointerTypeTouch = function (e) {
        if ('pointerType' in e.originalEvent)
            return (e.originalEvent.pointerType == 'touch');
        else true;
    };
    var getPos = function (e) {
        if ('touches' in e.originalEvent) {
            var touches = e.originalEvent.touches;
            if (touches.length < 1) return { x: 0, y: 0 };
            return { x: touches[0].pageX, y: touches[0].pageY };
        }
        return { x: e.pageX, y: e.pageY };
    };
    var ontouchstart = function (e) {
        if (isPointerTypeTouch(e) === false) return;
        startPos = getPos(e);
    }
    var ontouchmove = function (e) {
        if (startPos === null || isPointerTypeTouch(e) === false) return;
        var pos = getPos(e);
        var d = { x: startPos.x - pos.x, y: startPos.y - pos.y };
        if (Math.abs(d.x) >=40) {
            startPos = null;
            $(this).trigger(d.x < 0 ? 'swiperight' : 'swipeleft');
            return false;
        }
    }
    var ontouchend = function (e) {
        if (startPos === null || isPointerTypeTouch(e) === false) return;
        startPos = null;
    }

    var hideaddressbar = function () {
        setTimeout(function () { window.scrollTo(0, 1); }, 100);
    };

    $(document.body)
        .bind({
            // IE10
            'MSPointerDown': ontouchstart,
            'MSPointerMove': ontouchmove,
            'MSPointerUp': ontouchend,
            
            // IE11+
            'pointerdown': ontouchstart,
            'pointermove': ontouchmove,
            'pointerup': ontouchend,
            
            // Webkit, Gecko
            'touchstart': ontouchstart,
            'touchmove': ontouchmove,
            'touchend': ontouchend,
            'swiperight': function () {
                Present.prevSlide();
                hideaddressbar();
            },
            'swipeleft': function () {
                Present.nextSlide();
                hideaddressbar();
            }
        });

    hideaddressbar();

    $(window).bind('orientationchange', hideaddressbar);
});

// Go to page by hash of URL changed.
$(window).bind('hashchange', function () {
    var slideIndex = Present.hashToSlideIndex();
    if (Present.currentSlide != slideIndex)
        Present.showSlide(slideIndex);
});

// Go to page by drop down list changed.
$('.slideCount select').change(function () {
    Present.showSlide(parseInt($(this).val()));
    setTimeout(function () { document.body.focus(); }, 0);
});
</script>