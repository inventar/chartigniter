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
	 * Leitet folgenden Javascript-Code ein. Erzeugt ein Highcharts-Objekt,
	 * alle folgenden Einstellungen werden mithilfe von JSON eingefügt.
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
	 * Schließt das Highcharts Objekt und den Javascript-Code
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
		// Suche nach gesetzten Events
		$this->events('detect');

		$this->foot.= '<div id="'.$this->options['chart']['renderTo'].'"></div>';
		$this->body = json_encode($this->options);

		// Einsetzen des Events als ausführbarer Javascript-Code
		$this->events('solve');

		$render = $this->head.$this->body.$this->foot;

		$this->reset();

		return $render;
	}

	/**
	 * Diese Methode spürt gesetzte Events auf und ersetzt den JS-Code, der aufgrund es Events ausgeführt werden soll vor dem JSON-Encoding
	 * durch Platzhalter, weil der Code sonst zu einem gewöhnlichen String werden würde. Nach dem JSON-Encoding wird dieser Platzhalter samt doppelter
	 * Anführungszeichen aus der erzeugten JSON-Zeichenkette entfernt. Dadurch wird der JS-Code wirder ausführbar
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
				// Hierbei ist es egal, für wie viele Graph-Typen (Pie, Line, etc.) Optionen gesetzt wurden
				foreach($this->options['plotOptions'] as $plot => $plotoptions)
				{
					// Falls Events definiert wurden
					if(isset($plotoptions['events']) && !empty($plotoptions['events']))
					{
						// Durchlaufen aller Events (click, mouseover, usw.)
						foreach($plotoptions['events'] as $trigger => $jscode)
						{
							// Der JS-Code, der ausgeführt werden soll, wird in einer Klassenvariable abgelegt
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
		// Durch das Entfernen der doppelten Anführungszeichen, wird der JS-Code dann ausführbar!
		elseif($method === 'solve')
		{
			// Falls Events gefunden wurden
			if((is_array($this->events)) && (!empty($this->events)))
			{
				// Jedes gefundene Event wird durchlaufen
				foreach($this->events as $plot => $events)
				{
					// Mithilfe der Kombination Graphtyp (Plot) und Event (Trigger) als Array-Keys, können alle Events
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
	 * Reset-Methode - Setzt nach dem Rendern eines Graphen alle Klassenvariablen zurück,
	 * sodass zur Laufzeit problemlos mehrere Graphen mit einer Instanz erzeugt werden können.
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