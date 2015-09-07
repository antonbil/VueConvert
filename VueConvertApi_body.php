 <?php
if( class_exists('VueConvertLogic') != true )
{
   include "VueConvertLogic.php";
}
class VueConvertApi extends ApiBase {
	public function execute() {
		// Get specific parameters
		// Using ApiMain::getVal makes a record of the fact that we've
		// used all the allowed parameters. Not doing this would add a
		// warning ("Unrecognized parameter") to the returned data.
		// make sure to catch ALL parameters with $this->getMain()->getVal

		//parameters: $postfix,$prefix,$ymlcontent,$vuecontent,$vuename
		$postfix = $this->getMain()->getVal( 'postfix' );
		$prefix = $this->getMain()->getVal( 'prefix' );
		$ymlcontent = $this->getMain()->getVal( 'ymlcontent' );
		$vuecontent = $this->getMain()->getVal( 'vuecontent' );
		$vuename = $this->getMain()->getVal( 'vuename' );

		$result = $this->getResult();
		// do calculations
		$logic = new VueConvertLogic();
		$out=$logic->doConversion($postfix,$prefix,$ymlcontent,$vuecontent,$vuename);
	        // return results
		// Top level
		$this->getResult()->addValue( null, $this->getModuleName(), array ( 'templatetext' => $out->str,
		'templatetitle' =>$out->templateTitle,'outname' => $out->out_name,'imfilenamewoextension' => $out->imfilenamewoextension) );
		return true;
	}

	// Description
	public function getDescription() {
		return 'Convert Vue-file to Template.';
	}

	// allowed parameter.
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(//postfix,prefix,ymlcontent,vuecontent,vuename
			'postfix' => array (
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
			'prefix' => array (
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
			'ymlcontent' => array (
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
			'vuecontent' => array (
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
			'vuename' => array (
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
		) );
	}

	// Describe the parameter
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'postfix' => 'postfix description',
			'prefix' => 'prefix description',
			'ymlcontent' => 'ymlcontent description',
			'vuecontent' => 'vuecontent description',
			'vuename' => 'vuename description'
		) );
	}

	// Get examples
	public function getExamplesMessages() {
		return array(
                        // vueconvertapi-desc is the key to an i18n message explaining the example
			'api.php?action=vueconvert&postfix=post&format=xml'
			=> 'vueconvertapi-desc'
		);
	}
}