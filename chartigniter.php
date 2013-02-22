<?php

/**
 * PHP-Klasse zum erzeugen von Highcharts-Graphen ohne selbst Javascript verwenden zu m�ssen.
 * Setzt allerdings die eingebundene Highcharts-Javascript bibliothek voraus
 *
 * --> http://www.highcharts.com/
 *
 * @author Sebastian Koine <sebkoine@gmail.com>
 * @version 1.0 2013-02-21
 *
 */
class Chartigniter
{
	/**
	 * Leitet folgenden Javascript-Code ein. Erzeugt ein Highcharts-Objekt,
	 * alle folgenden Einstellungen werden mithilfe von JSON eingef�gt.
	 *
	 * @var string
	 */
	private $head = "<script type='text/javascript'>
					 var chart;
					 chart = new Highcharts.Chart(";

	/**
	 * Erstellter Inhalt, wird im JSON-Format hier gespeichert und zwischen
	 * $head und $foot gesetzt
	 *
	 * @var string
	 */
	private $body = "";

	/**
	 * Schlie�t das Highcharts Objekt und den Javascript-Code
	 *
	 * @var string
	 */
	private $foot = ");</script>";

	/**
	 * Alle Einstellungen bez�glich des Graphen werden hier als Array gesammelt und Sp�ter
	 * in JSON kodiert, damit es durch Javascript ausf�hrbar wird. Alle Einstellungsm�glichkeiten
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
		$this->options = array('chart' => array('renderTo' => 'container',
												'backgroundColor' => null));
	}


	/**
	 * Magische Methode. Nimmt alle Einstellungsaufrufe auf und speichert sie in die Klassenvariable
	 * $options! N�here Informationen im Wiki
	 *
	 * --> https://github.com/ThEmKay/chartigniter/wiki
	 *
	 * Dabei k�nnen Parameter einzeln
	 * --> $this->chart('type', 'pie');
	 *
	 * Oder mehrere als Array �bergeben werden
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
	 * Magische get-Methode. Holt bisher nur den erzeugten Javascript/HTML-Code zum
	 * Darstellen des Graphen mit Hilfe von $this->graph, ansonsten kommt false zur�ck
	 *
	 * @param string $get
	 * @return mixed
	 */
	public function __get($get)
	{
		switch($get)
		{
			case 'graph': $this->foot.= '<div id="'.$this->options['chart']['renderTo'].'"></div>';
						  $this->body = json_encode($this->options);
						  return $this->head.$this->body.$this->foot; break;
			default: return false; break;
		}
	}
	
	



}