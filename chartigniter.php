<?php
/**
 * PHP-Klasse zum erzeugen von Highcharts-Graphen ohne selbst Javascript verwenden zu m�ssen.
 * !!! Setzt allerdings die eingebundene Highcharts-Javascript bibliothek voraus !!!
 *
 * http://www.highcharts.com/
 *
 * @author Sebastian Koine <sebkoine@gmail.com>
 * @version 1.0 2013-02-21
 * @version 1.1 2013-02-22 - Events k�nnen verwendet werden
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
	private $head = "<script type='text/javascript'>var chart;chart = new Highcharts.Chart(";

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
	 * Speichert den JS-Code aus gefundenen Events zwischen
	 *
	 * @var array
	 */
	private $events = array();

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
		$this->options = array('chart' => array('renderTo' => 'container', 'backgroundColor' => null));
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
	 * String zusammen und gibt diesen komplett zur�ck.
	 *
	 * @return string
	 */
	public function render()
	{
		// Suche nach gesetzten Events
		$this->events('detect');

		$this->foot.= '<div id="'.$this->options['chart']['renderTo'].'"></div>';
		$this->body = json_encode($this->options);

		// Einsetzen des Events als ausf�hrbarer Javascript-Code
		$this->events('solve');

		$render = $this->head.$this->body.$this->foot;

		$this->reset();

		return $render;
	}

	/**
	 * Diese Methode sp�rt gesetzte Events auf und ersetzt den JS-Code, der aufgrund es Events ausgef�hrt werden soll vor dem JSON-Encoding
	 * durch Platzhalter, weil der Code sonst zu einem gew�hnlichen String werden w�rde. Nach dem JSON-Encoding wird dieser Platzhalter samt doppelter
	 * Anf�hrungszeichen aus der erzeugten JSON-Zeichenkette entfernt. Dadurch wird der JS-Code wirder ausf�hrbar
	 *
	 * @param string $method
	 * @return boolean
	 */
	private function events($method)
	{
		// DETECT - Auffinden von gesetzten Events in den Plot-Options
		if($method === 'detect')
		{
			if(isset($this->options['plotOptions']))
			{
				// Alle gesetzten Plot-Options werden durchlaufen.
				// Hierbei ist es egal, f�r wie viele Graph-Typen (Pie, Line, etc.) Optionen gesetzt wurden
				foreach($this->options['plotOptions'] as $plot => $plotoptions)
				{
					// Falls Events definiert wurden
					if(isset($plotoptions['events']) && !empty($plotoptions['events']))
					{
						// Durchlaufen aller Events (click, mouseover, usw.)
						foreach($plotoptions['events'] as $trigger => $jscode)
						{
							// Der JS-Code, der ausgef�hrt werden soll, wird in einer Klassenvariable abgelegt
							// Durch die Kombination aus Graphtyp und Event entsteht eine eindeutige Zuordnung
							$this->events[$plot][$trigger] = $jscode;

							// Vor dem JSON-Encode wird hier der JS-Code durch einen Platzhalter ersetzt. Dieser ist eindeutig durch
							// die Kombination aus Graphtyp (z.B. Pie) und Event (z.B. Click) definiert
							$this->options['plotOptions'][$plot]['events'][$trigger] = '%'.$plot.'_'.$trigger.'%';
						}
					}
				}
			}
			return true;
		}
		// SOLVE - Ersetzt die durch DETECT gesetzten Platzhalter nach dem JSON-Encode durch den passenden JS-Code
		// Durch das Entfernen der doppelten Anf�hrungszeichen, wird der JS-Code dann ausf�hrbar!
		elseif($method === 'solve')
		{
			// Falls Events gefunden wurden
			if((is_array($this->events)) && (!empty($this->events)))
			{
				// Jedes gefundene Event wird durchlaufen
				foreach($this->events as $plot => $events)
				{
					// Mithilfe der Kombination Graphtyp (Plot) und Event (Trigger) als Array-Keys, k�nnen alle Events
					// eindeutig zum Platzhalter zugeordnet werden.
					foreach($events as $trigger => $jscode)
					{
						$this->body = str_replace('"%'.$plot.'_'.$trigger.'%"', $jscode, $this->body);
					}
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Reset-Methode - Setzt nach dem Rendern eines Graphen alle Klassenvariablen zur�ck,
	 * sodass zur Laufzeit problemlos mehrere Graphen mit einer Instanz erzeugt werden k�nnen.
	 *
	 * @return null
	 */
	private function reset()
	{
		$this->body = "";
		$this->events = array();
		$this->options = array('chart' => array('renderTo' => 'container', 'backgroundColor' => null));

		return null;
	}

}