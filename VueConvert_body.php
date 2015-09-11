<?php
/*
 *
 * ====================== VueConvert_body.php ===============================
 * Revision Information
 *   Changed: april 10 2015
 *   Revision: 1.0
 *   Last Update By: Anton Bil
* prerequisites: ruby and ruby-xml-simple packages must be installed
 */
//small change
if( class_exists('VueConvertLogic') != true )
{
   include "VueConvertLogic.php";
}
class VueConvertSpecialPage extends SpecialPage
{
    const JAVASCRIPTTEXT=<<<'EOD'
<script type="text/javascript">
function inbetween(s,total)
//get text inbetween s and end of line inside string total
{var n=total.search(s);
  if (n<0) return "";
  var sub=total.substring(n+s.length);
  var n=sub.search("\n");
  return sub.substring(1,n).replace(/"+/g, "").trim();
}
function readYmlFile(evt) {
  var f = evt.target.files[0]; 
  //var fname=f.name;
  if (f) {
    var r = new FileReader();
    r.onload = function(e) { 
      var contents = e.target.result;
      document.getElementById("prefix").value = inbetween("prefix:",contents);
      document.getElementById("postfix").value = inbetween("postfix:",contents);
      //var vname = inbetween("vue_file_name:",contents);
      //alert(fname);
    } ;
    r.readAsText(f);
  }
}

document.getElementById("ymlinput").addEventListener("change", readYmlFile, false);
</script>
EOD;
    const DEFAULTYML = <<<'EOD'
prefix: "GC "
postfix: " VN"
offset_x: 16
offset_y: 16
scale_x: 1.5
scale_y: 1.5
select:
reject:
  -"Context/situatie/verhaal"
  -"Begrippen (toegang tot de thesaurus)"
  -"Begrip"
  -"Instantie van begrip"
  -"Menuitem"
  -"Casus"
map:
  "4+1 model": "4 + 1 resilience model"
  "Er zijn": "Zorgdenkwijze er zijn"
  "In de kracht zetten": "Zorgdenkwijze in de kracht zetten"
EOD;

	function __construct() {
		parent::__construct( "VueConvertSpecialPage","vueconvert" );
	}
	function execute($par) {
		//restrict acces to users wo have got here by typing the url
		if (  !$this->userCanExecute( $this->getUser() )  ) {
			$this->displayRestrictionError();
			return;
		}

		global $wgRequest;
		
		$this->setHeaders ();
		
		// Handle whether the form was posted or not
		if ($wgRequest->wasPosted ()) {
			$displaymethod = $_POST ['displaymethod'];
			if ($displaymethod == "onscreen") {//first screen added, so show second screen
				$ymltempname=$wgRequest->getFileTempname( "ymlinput");
				$ymlname=$wgRequest->getFilename( "ymlinput");
				if ($ymlname) $ymlcontent = file_get_contents($ymltempname);
				else {
				    //default content yml-file
				    $ymlcontent = self::DEFAULTYML;
				}
				$vuetempname=$wgRequest->getFileTempname( "vueinput");
				$vuename=$wgRequest->getFilename( "vueinput");
				if ($vuename) {
				  $vuecontent = file_get_contents($vuetempname);
				  $this->processForm ($_POST ["postfix"], $_POST ['prefix'], $ymlcontent, $vuecontent, $vuename );
				}
				else
				  $this->displayForm (wfMessage( 'error-vue-file' ));//error in file, so show first screen
			} else if ($displaymethod == "download")//third screen, data must be downloaded
				$this->downloadtext ( $_POST ['imfile'], $_POST ['imtext'] );
			else {
				$this->displayForm ();//not second or third screen, so show first
			}
		} else {
			$this->displayForm ();//first enter, so show first screen
		}
	}

function downloadtext($imfile,$imtext){
	    $header='Content-Disposition: attachment; filename="'.$imfile.'";';
	    header('Content-Type: application/txt');
	    // tell the browser we want to save it instead of displaying it
	    header($header);
	    echo $imtext; // push it out
	    exit();  
}

/*remove all characters after trailing slash
*/
function removeAfterSlash($url){
  return substr($url, 0,strrpos($url, '/')+1);
}

/*
  * display information $str in text area
  * $out_name = name of output-file to be stored in hidden field.
  */
function displayintextarea($str,$out_name,$vuename,$imfilename,$prefix,$postfix,$templateTitle){

  $content=array();
  $arr = explode("\n", $str);
//TODO vertaal html-tags naar XML zoals hierboven aangegeven
  array_push($content,'<h1>'. wfMessage( 'result-conversion' )->text().' '.$vuename.'</h1>');
  array_push($content,'<h2>'. wfMessage( 'copy-paste' )->text().'</h2>');
  array_push($content,'<a href="'.$this->removeAfterSlash($_SERVER['REQUEST_URI']).$templateTitle.'">'.$templateTitle.'</a> '. wfMessage( 'created' )->text());
  array_push($content,'<form action="#" method="post" id="download" enctype="multipart/form-data">');
  array_push($content,'<input type="hidden" name="displaymethod" value="download">');
  array_push($content,"<input type=\"hidden\" name=\"imfile\" value=\"".$out_name."\">");
  array_push($content,'<textarea rows="30" cols="120" name="imtext">');
 
  foreach($arr as $line) {
	  array_push($content,$line."\n");
  }
  array_push($content,'</textarea>');
  array_push($content,'<p>');
  array_push($content,'<h2>'. wfMessage( 'alternative-download' )->text().'</h2>');
  array_push($content,'<input type="submit" value="'. wfMessage( 'download' )->text().'" />');
  array_push($content,'</form>');
  return $content;
}

function number($nr){
  return XML::openElement('i'). $nr.'. '.XML::closeElement('i');
}

  function displayForm($error = null){
    global $wgOut;

    $htmlstr = XML::openElement('h2')
      .  wfMessage( 'vue-file-omzetten' )->text()
      . XML::closeElement('h2');




  $htmlstr .= XML::openElement('br');
  $htmlstr .= XML::openElement('h3'). wfMessage( 'choose-files' )->text().XML::closeElement('h3');
  $htmlstr .= XML::openElement('br');
    $htmlstr .= XML::openElement('form',
      array(
        'method' => 'post',
        'action' => '',//respond to same page
	'enctype' => "multipart/form-data",
	'onsubmit' => ""
       )
      );

  $htmlstr .= XML::openElement('input',
      array(
        'type' => 'hidden',
        'name' => 'displaymethod',
	'value' => "onscreen"
       )
      );

  $htmlstr .= $this->number('1').'<label class="field" for="ymlinput">'. wfMessage( 'yml-file' )->text().':</label>';
  $htmlstr .= '<input type="file" name="ymlinput" id="ymlinput" />';
  //$htmlstr .= '<input type="hidden" name="ymlcontent" id="ymlcontent">';
  //$htmlstr .= '<input type="hidden" name="ymlname" id="ymlname">';
  $htmlstr .= $this->number('2').XML::openElement('label',
      array(
        'class' => 'field',
        'for' => 'vueinput',
       )
      ). wfMessage( 'vue-file' )->text().':'.XML::closeElement('label');
//TODO vertaal html-tags naar XML zoals hierboven aangegeven
  $htmlstr .= '<input type="file" name="vueinput" id="vueinput" />';
  if ($error) {
    $htmlstr .= '<br/>'.$error."<br/>";
  }
  //javascript to be inserted.
  $htmlstr .= self::JAVASCRIPTTEXT;
  $htmlstr .= XML::openElement('p');
  $htmlstr .= XML::openElement('hr');
  $htmlstr .= XML::openElement('h3'). wfMessage( 'prefix-postfix' )->text().XML::closeElement('h3');
  $htmlstr .= $this->number('3a').wfMessage( 'prefix' )->text().': <input type="text" name="prefix" id="prefix"><br>';
  $htmlstr .= $this->number('3b').wfMessage( 'postfix' )->text().': <input type="text" name="postfix" id="postfix"><br>';
  $htmlstr .= XML::openElement('hr');
  $htmlstr .= XML::openElement('p');
  $htmlstr .= $this->number('4').'<input type="submit" value="'.wfMessage( 'convert' )->text().'" />';
  $htmlstr .= XML::closeElement('form');

  $wgOut->addHTML($htmlstr);


  }

  function processForm($postfix,$prefix,$ymlcontent,$vuecontent,$vuename){
    global $wgOut, $wgScript;
    $htmlstr = '';

    $htmlstr .= XML::openElement('form',
      array(
        'method' => 'post',
        'action' => '',//respond to same page
       )
      );
    //this is second screen, but you can go back to first
    $htmlstr .= '<input type="hidden" name="displaymethod" value="first">';
    $htmlstr .= '<input type="submit" value="'. wfMessage( 'back' )->text().'" />';
    $htmlstr .= XML::closeElement('form');

    $logic = new VueConvertLogic();
    $out=$logic->doConversion($postfix,$prefix,$ymlcontent,$vuecontent,$vuename);
    //output results
    $imagefiletext=$this->displayintextarea($out->imcontents,$out->out_name,$vuename,$out->imfilenamewoextension,$prefix,$postfix,$out->templateTitle);
    foreach($imagefiletext as $line)
    {
	$htmlstr .= $line;
    }
    $wgOut->addHTML($htmlstr);

   }

}
