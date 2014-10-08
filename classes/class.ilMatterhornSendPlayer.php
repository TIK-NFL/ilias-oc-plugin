<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilMatterhornSendPlayer
*
* Sends the player files for Matterhorn, setting proper paths in the js files
*
* @auther Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
*
*/

class ilMatterhornSendPlayer
{

	var $basename = "/Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul";
	
	
	/**
	* relative file path from ilias directory (without leading /)
	* @var string
	* @access private
	*/
	var $subpath;

	/**
	 * the base path of the ilias installation
	 * @var string
	 * @access private
	 */
	var $iliasPrefix; 
	
	/**
	 * the id of the matterhorn episode
	 * @var string
	 * @access private
	 */
	var $episode_id;
	
	
	/**
	* absolute path in file system
	* @var string
	* @access private
	*/
	var $file;	

	/**
	 * Stores if this is a request for an episode.
	 * @var boolean
	 * @access private
	 */
	var $episodeRequest;
	
	
	/**
	 * The mimetype to be sent
	 * will be determined if null
	 * @var string
	 * @access private
	 */
	var $mimetype = null;
	
	
	/**
	* errorcode for sendError
	* @var integer
	* @access private
	*/
	var $errorcode;

	    
	/**
	* errortext for sendError
	* @var integer
	* @access private
	*/
	var $errortext;


	/**
	* Constructor
	* @access	public
	*/
	function ilMatterhornSendPlayer()
	{
/*		global $ilUser, $ilAccess, $lng, $ilLog;
		
		$this->lng =& $lng;
		$this->ilAccess =& $ilAccess;
		$this->params = array();
		$this->episodeRequest = false;
*/		
		// get the requested file and its type
#		$uri = parse_url($_SERVER["REQUEST_URI"]);
#		parse_str($uri["query"], $this->params);		
		
		$client_start = strpos($_SERVER['PHP_SELF'], $this->basename."/") + strlen($this->basename)+1;
		$this->iliasPrefix = substr(substr($_SERVER['PHP_SELF'],0,$client_start),strpos($_SERVER['PHP_SELF'],'/')+1, -1*strlen($this->basename));
		$this->subpath = $_SERVER["DOCUMENT_ROOT"].substr($_SERVER['PHP_SELF'],0,$client_start);
		$this->file = substr($_SERVER['REQUEST_URI'], $client_start);
		

/*				
		// debugging
		echo "<pre>\n";
		//var_dump($uri);
		//var_dump($this->params);
		echo "REQUEST_URI:         ". $_SERVER["REQUEST_URI"]. "\n";
		echo "Parsed URI:          ". $uri["path"]. "\n";
		echo "DOCUMENT_ROOT:       ". $_SERVER["DOCUMENT_ROOT"]. "\n";
		echo "PHP_SELF:            ". $_SERVER["PHP_SELF"]. "\n";
		echo "SCRIPT_NAME:         ". $_SERVER["SCRIPT_NAME"]. "\n";
		echo "SCRIPT_FILENAME:     ". $_SERVER["SCRIPT_FILENAME"]. "\n";
		echo "subpath:             ". $this->subpath. "\n";
		echo "file:                ". $this->file. "\n";
		echo "</pre>";

		#		echo phpinfo();
		exit;
		
	*/	
		/*
		if (!file_exists($this->file))
		{
			$this->errorcode = 404;
			$this->errortext = $this->lng->txt("url_not_found");
			return false;
		}
		*/
	}

	
	public function checkFileAccess(){
		return true;
	}
	
		
	
	/**
	* Send the requested file as if directly delivered from the web server
	* @access	public
	*/
	public function sendFile()
	{
		if (preg_match("/\.js$/i", $this->subpath.$this->file)) {
			header("Content-Type: application/javascript");
		} else if (preg_match("/\.css$/i", $this->subpath.$this->file)) {
			header("Content-Type: text/css");
		} else {
		
		$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
			header("Content-Type: ".finfo_file($finfo, $this->subpath.$this->file));
		finfo_close($finfo);
		}
		if (preg_match("/\.js$|\.css$|\.html/i", $this->subpath.$this->file)) {
			$file = file_get_contents($this->subpath.$this->file);
			if($this->iliasPrefix != ""){
				$file = str_replace('%iliasbasedir%', $this->iliasPrefix, $file);
			} else {
				$file = str_replace('%iliasbasedir%/', '', $file);
			}
			print $file;
		} else { 
			readfile($this->subpath.$this->file);
		}
	}
	
	/**
	* Send an error response for the requested file
	* @access	public
	*/
	public function sendError()
	{

		switch ($this->errorcode)
		{
			case 404:
				header("HTTP/1.0 404 Not Found");
				return;
//				break;
			case 403:
			default:
				header("HTTP/1.0 403 Forbidden");
				return;
				
				//break;
		}
		header("HTTP/1.0 404 Not Found");
		return;		
	}	
}
?>
