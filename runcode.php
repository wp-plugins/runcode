<?php
/*
 Plugin Name: RunCode
 Plugin URI: http://blog.sunshow.net/archives/367.html
 Description: Run html code in a textarea
 Version: 1.0
 Author: Sunshow
 Author URI: http://www.sunshow.net
 */
add_action('wp_head','runcode_run');

function runcode_make_random_str($length) //generate random id
{
	$possible = "0123456789_" . "abcdefghijklmnopqrstuvwxyz". "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$str = "";
	while (strlen($str) < $length) {
		$str .= substr($possible, (rand() % strlen($possible)), 1);
	}
	return($str);
}

function runcode_run() {
echo <<<END
<script type="text/javascript">
function runcode_open_new(element)
{
	var code = document.getElementById(element).value;
	var win = window.open("", "", "");
	win.opener = null;
	win.document.write(code);
	win.document.close();
}
function runcode_copy(element)
{
	var codeobj = document.getElementById(element);
	var meintext = codeobj.value;
	try {
	 if (window.clipboardData)
	   {
	  
	   // the IE-manier
	   window.clipboardData.setData("Text", meintext);
	  
	   // waarschijnlijk niet de beste manier om Moz/NS te detecteren;
	   // het is mij echter onbekend vanaf welke versie dit precies werkt:
	   }
	   else if (window.netscape)
	   {
	  
	   // dit is belangrijk maar staat nergens duidelijk vermeld:
	   // you have to sign the code to enable this, or see notes below
	   netscape.security.PrivilegeManager.enablePrivilege('UniversalXPConnect');
	  
	   // maak een interface naar het clipboard
	   var clip = Components.classes['@mozilla.org/widget/clipboard;1']
					 .createInstance(Components.interfaces.nsIClipboard);
	   if (!clip) return;
	  
	   // maak een transferable
	   var trans = Components.classes['@mozilla.org/widget/transferable;1']
					  .createInstance(Components.interfaces.nsITransferable);
	   if (!trans) return;
	  
	   // specificeer wat voor soort data we op willen halen; text in dit geval
	   trans.addDataFlavor('text/unicode');
	  
	   // om de data uit de transferable te halen hebben we 2 nieuwe objecten
	   // nodig om het in op te slaan
	   var str = new Object();
	   var len = new Object();
	  
	   var str = Components.classes["@mozilla.org/supports-string;1"]
					.createInstance(Components.interfaces.nsISupportsString);
	  
	   var copytext=meintext;
	  
	   str.data=copytext;
	  
	   trans.setTransferData("text/unicode",str,copytext.length*2);
	  
	   var clipid=Components.interfaces.nsIClipboard;
	  
	   if (!clip) return false;
	  
	   clip.setData(trans,null,clipid.kGlobalClipboard);
	  
	   }
	} catch (e) {
END;

echo "alert('" . __('Because of security policy reasons, this feature has been banned by your browser.Close this window, and press "Ctrl+C" to copy the code.', 'runcode') . "');";

echo <<<END
		codeobj.focus();
	}
	codeobj.select();
   return false;
}
</script>
END;
}

$RunCode = new RunCode();
add_filter('the_content', array(&$RunCode, 'part_one'), -500);
add_filter('the_content', array(&$RunCode, 'part_two'),  500);


unset($RunCode);

class RunCode
{
    // The blocks array that holds the block ID's and their real code blocks
    var $blocks = array();


	function RunCode() {
		load_plugin_textdomain('runcode', 'wp-content/plugins/runcode', 'runcode');
	}


    /****************************************************************************
     * part_one
     *    > Replace the code blocks with the block IDs
     ****************************************************************************/
	function part_one($content)
    {
		$cols = 50;
		$rows = 5;
		$run_button = __('Run', 'runcode');
		$copy_button = __('Copy', 'runcode');
		$run_tips = __('Tips:You can change the code before run.', 'runcode');
		$str_pattern = "/(\<runcode(.*?)\>(.*?)\<\/runcode\>)/is";
		if (preg_match_all($str_pattern, $content, $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$code = htmlspecialchars($matches[3][$i]);

				$code = preg_replace("/(\s*?\r?\n\s*?)+/", "\n", $code);

				$num = runcode_make_random_str(6);
				$id = "runcode_$num";

				$blockID = "<p>++RunCode_BLOCK_$num++</p>";

				$innertext = "<div class=\"runcode\">" . "\n";
				$innertext .= "<p><textarea name=\"runcode\" class=\"runcode_text\" id=\"" . $id . "\">" . $code . "</textarea></p>" . "\n";

				$innertext .= "<p><input type=\"button\" value=\"" . $run_button . "\" class=\"runcode_button\" onclick=\"runcode_open_new('" . $id . "');\"/> ";
				$innertext .= "<input type=\"button\" value=\"" . $copy_button . "\" class=\"runcode_button\" onclick=\"runcode_copy('" . $id . "');\"/> ";
				$innertext .= $run_tips . "</p>" . "\n";
				$innertext .= "</div>";

				$this->blocks[$blockID] = $innertext;

				$content = str_replace($matches[0][$i], $blockID, $content);
			}
		}
		return $content;
	}

    /****************************************************************************
     * part_two
     *    > Replace the block ID's from part one with the actual code blocks
     ****************************************************************************/
    function part_two($content)
    {
        if (count($this->blocks)) {
            $content = str_replace(array_keys($this->blocks), array_values($this->blocks), $content);
            $this->blocks = array();
        }

        return $content;
    }
}
?>
