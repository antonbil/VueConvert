
<?php
class VueConvertLogic
{
  const TEMPLATE = 'Template';


/*
call with $this->getUser()->editToken()
*/
  public function __construct() {
  }//logic
/*
save result in template-file in wiki
*/
function saveResultAsTemplate($str,$out_name,$vuename,$prefix,$postfix){
  //remove extension from filename
  $path_parts = pathinfo($vuename);

  $filename = $path_parts['filename']; // Since PHP 5.2.0
  //$main=$this;
  $main=RequestContext::getMain();
  //get token for storing file
  $user = $main->getUser(); // Or User::newFromName, etc.
  $token = $user->editToken();

  //set parameters to save $str in template
  $templateTitle='IM '.$prefix .' '.$filename .' '.$postfix;
  $params = new DerivativeRequest( 
//In an API module context, one can use $this->getMain()->getRequest()
//same goes for getUser()
//You can access the main request context using RequestContext::getMain();
	  $main->getRequest(),
	  array(
	    'action' => 'edit',
	    'title' => ''. self::TEMPLATE.':'.$templateTitle,
	    //'section' => 0,//Omit to act on the entire page
            'basetimestamp' => wfTimestamp( TS_ISO_8601, wfTimestampNow() ),
	    'summary' => 'Image template '.$filename,
	    'text' => $str,
	    'token' => $token),
	  true
  );


  //save template in wiki
  $api = new ApiMain( $params ,true);//true = enable write: important!
  $api->execute();
  /*$tTitle = Title::makeTitle( NS_TEMPLATE, $templateTitle );
	
  #now create the page    
  $templatePage = new Article( $tTitle );
  $templatePage->doEdit( $str, 'Image template '.$filename, EDIT_NEW );*/

  //return template title to be used in calling function
  return ''. self::TEMPLATE.':'.$templateTitle;
}

function getparam($arr,$paramdesc) {
	/*
	 * $arr = array to be checked
	 * $paramdesc = parameter to be searched. If parameter exists value is returned
	 */
  $retvalue="";
  foreach($arr as $line) {
    $pos = strpos($line,$paramdesc);
    if(!($pos === false)) {
      $retvalue=substr($line,strlen($paramdesc)+2);
    }
  }
  return $retvalue;
}
function changeparam($arr,$paramdesc,$paramvalue) {
	/*
	 * add parameter/value pair to array
	 * $arr=array to be checked
	 * $paramdesc=parameter to be searched
	 * $paramvalue=value of new or existing parameter
	 * return: copy of array with new par/valuepair, or changed one if parameter already exists
	 */
  $newarr=[];
  foreach($arr as $line) {
    $pos = strpos($line,$paramdesc);
    $found=false;
    if($pos === false) {
	  array_push ($newarr,$line);
    }
    else {
	  array_push ($newarr,$paramdesc.": ".$paramvalue);
	  $found=true;
    }
  }
  if (!$found) {array_push ($newarr,$paramdesc.": ".$paramvalue);}
  return $newarr;
}

    //input: $vuename, $prefix,$postfix,$ymlcontent,$vuecontent,$vuename
    //output: $str, $templateTitle ,$out_name, $imfilenamewoextension
  public function doConversion($postfix,$prefix,$ymlcontent,$vuecontent,$vuename){
    global $wgTmpDirectory;
    //create hash for filename(s)
    $hash=hash('ripemd160', date("D M d, Y G:i").time().$vuecontent);
    //define filenames to be used
    $ymlfilename = $wgTmpDirectory."/".$hash.".yml";
    $imfilename = $wgTmpDirectory."/".$hash.".im";
    $imfilenamewoextension = $wgTmpDirectory."/".$hash;
    $vuefilename = $wgTmpDirectory."/".$hash;
    $rubyname=realpath(dirname(__FILE__)).'/genim.rb';

    $arr = explode("\n", $ymlcontent);
    //output-file name
    $val=$this->getparam($arr,'im_file_name');
    $out_name="out.im";
    if (strlen($val)>0)
      //remove non-printable characters
      $out_name=preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $val);

    //parameters to be added to yml-file
    //see if postfix and prefix need to be changed
    if (strlen($postfix)>0)
      $arr = $this->changeparam($arr,'postfix','" '.$postfix.'"');
    if (strlen($prefix)>0)
      $arr = $this->changeparam($arr,'prefix','"'.$prefix.' "');
    //set source- and output-file to the correct internal names
    $arr = $this->changeparam($arr,'im_file_name',$imfilename);
    $arr = $this->changeparam($arr,'vue_file_name',$vuefilename);
    //write result to yml-file, to be used by ruby script
    $fp = fopen($ymlfilename,"wb");
    foreach($arr as $line) {
      fwrite($fp,$line.PHP_EOL);
    }
    fclose($fp);
    //save vue-file to be read
    $fp = fopen($vuefilename,"wb");
    fwrite($fp,$vuecontent);
    fclose($fp);
    //now execute ruby-script 
    $rubycommand="ruby ".$rubyname." ".$ymlfilename;
    exec($rubycommand);
    $str=file_get_contents($imfilename);
    //replace local filename for vue-filename
    $str = str_replace($imfilename, pathinfo($vuename, PATHINFO_FILENAME), $str);
    //save result in wiki as template
    $templateTitle=$this->saveResultAsTemplate($str,$out_name,$vuename,$prefix,$postfix);
//var_dump($str);
    //four return parameters; add them together
    $out->str=$str;$out->templateTitle=$templateTitle;$out->out_name=$out_name;$out->imfilenamewoextension=$imfilenamewoextension;
    //remove three temporary files
    unlink ($ymlfilename );
    unlink ($imfilename );
    unlink ($vuefilename );
    return $out;
  }

//end logic
}
?>
 
