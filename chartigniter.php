<?php
/**
 * PHP-Klasse zum erzeugen von Highcharts-Graphen ohne selbst Javascript verwenden zu müssen.
 * !!! Setzt allerdings die eingebundene Highcharts-Javascript bibliothek voraus !!!
 *
 * http://www.highcharts.com/
 *
 * @author Sebastian Koine <sebkoine@gmail.com>
 * @version 1.0 2013-02-21
 * @version 1.1 2013-02-22 - Events können verwendet werden
 *
 */
class Chartigniter
{
	/**
	 * Erstellter Inhalt, wird im JSON-Format hier gespeichert und zwischen
	 * $head und $foot gesetzt
	 *
	 * @var string
	 */
	private $body = "";
	
	private $aEncodeKeys = array();
	
	private $aOriginalKeys = array();
	
	private $aGermanChar = array("Ä", "ä", "Ö", "ö", "Ü", "ü", "ß");
	
	private $aGermanCharAscii = array("&Auml;", "&auml;", "&Ouml;", "&ouml;", "&Uuml;", "&uuml;", "&szlig;");

	/**
	 * Alle Einstellungen bezüglich des Graphen werden hier als Array gesammelt und Später
	 * in JSON kodiert, damit es durch Javascript ausführbar wird. Alle Einstellungsmöglichkeiten
	 * sind der Highcharts-Dokumentation zu entnehmen (http://api.highcharts.com)
	 * Anwendungsbeispiele sind im charigniter-Wiki auf github zu finden
	 *
	 * --> https://github.com/ThEmKay/chartigniter/wiki
	 *
	 * @var string
	 */
	private $options = '';

	public function __construct()
	{
		$this->options = array('chart' => array('renderTo' => 'container', 'backgroundColor' => null));
	}

	/**
	 * Magische Methode. Nimmt alle Einstellungsaufrufe auf und speichert sie in die Klassenvariable
	 * $options! Nähere Informationen im Wiki
	 *
	 * --> https://github.com/ThEmKay/chartigniter/wiki
	 *
	 * Dabei können Parameter einzeln
	 * --> $this->chart('type', 'pie');
	 *
	 * Oder mehrere als Array übergeben werden
	 * --> $this->chart(array('type', 'pie',
	 * 						  'backgroundColor', '#000'))
	 *
	 * @param string $option
	 * @param mixed $values string/array
	 */
	public function __call($option, $values)
	{
		if(!empty($values))
		{
			if(is_array($values[0]))
			{
				foreach($values[0] as $opt => $val)
				{
					$this->options[$option][$opt] = $val;
				}
			}
			else
			{
				if(isset($values[0]) && isset($values[1]))
				{
					$this->options[$option][$values[0]] = $values[1];
				}
			}
		}
	}

	/**
	 * Magische get-Methode.
	 *
	 * @param string $get
	 * @return null
	 */
	public function __get($get)
	{
		return;
	}

	/**
	 * Render-Methode. Wandelt alle Einstellungen in JSON um, setzt den Javascript-Code
	 * String zusammen und gibt diesen komplett zurück.
	 *
	 * @return string
	 */
	public function render()
	{
		$divs = '';
		
		$this->body = $this->set_local_options($this->options);
		$this->body = $this->encode($this->body);
		
		$renderId = $this->options['chart']['renderTo'];
		
		$embed  = '<script type="text/javascript">'."\n";
		$embed .= '$(function(){'."\n";
		
		$embed .= 'var '.$renderId.' = new Highcharts.Chart('.$this->body.');'."\n";
		$divs  .= '<div id="'.$renderId.'"></div>'."\n";
		
		$embed .= '});'."\n";
		
		$embed .= '</script>'."\n";
		$embed .= $divs;
		
		$this->reset();
		
		return $embed;
	}
	
	public function encode($options)
	{
		$options = preg_replace('(\\\)', '', json_encode($options));
		$options = str_replace($this->aEncodeKeys, $this->aOriginalKeys, $options);
		$options = str_replace($this->aGermanCharAscii, $this->aGermanChar, $options);
		return preg_replace('[^u]', '', $options);
	}
	
	
	private function set_local_options($aOptions = array(), $aVal = array())
	{
		foreach($aOptions as $sKey => $aValOptions)
		{
			if(is_string($sKey))
			{
				if(is_object($aVal))
				{
					$aVal[$sKey] = array();
					$aVal[$sKey] = $this->set_local_options($aValOptions, $aVal[$sKey]);
				}
				else
				{
					$aVal[$sKey] = $this->encodeFunction($aValOptions);
				}
			}
		}
		
		return $aVal;
	}
	
	private function encodeFunction($aEncodeArray = array(), $aEncodeOptions = array())
	{
		if(is_array($aEncodeArray))
		{
			foreach($aEncodeArray as $sEncodeKey => $aEncodeContent)
			{
				if(is_string($sEncodeKey) && is_string($aEncodeContent))
				{
					$aEncodeOptions[$sEncodeKey] = $this->delimit_function($aEncodeContent);
				}
				else
				{
					$aEncodeOptions[$sEncodeKey] = array();
					$aEncodeOptions[$sEncodeKey] = $this->encodeFunction($aEncodeContent, $aEncodeOptions[$sEncodeKey]);
				}
			}
		}
		elseif(is_string($aEncodeArray))
		{
			$aEncodeOptions = $this->delimit_function($aEncodeArray);
		}
		else
		{
			$aEncodeOptions = $aEncodeArray;
		}
		
		return $aEncodeOptions;
	}
	
	private function delimit_function($string = '')
	{
		if(strpos($string, 'function(') !== false)
		{
			$this->aOriginalKeys[] = $string;
			$string = '$$' . $string . '$$';
			$this->aEncodeKeys[] = '"' . $string . '"';
		}
		else
		{
			$string = str_replace($this->aGermanChar, $this->aGermanCharAscii, $string);
		}
		return $string;
	}

	/**
	 * Reset-Methode - Setzt nach dem Rendern eines Graphen alle Klassenvariablen zurück,
	 * sodass zur Laufzeit problemlos mehrere Graphen mit einer Instanz erzeugt werden können.
	 *
	 * @return null
	 */
	private function reset()
	{
		$this->body = "";
		$this->options = array('chart' => array('renderTo' => 'container', 'backgroundColor' => null));

		return null;
	}
}
?>