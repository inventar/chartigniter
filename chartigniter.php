<?php

class Chartigniter
{

	private $head = "<script type='text/javascript'>
					 var chart;
					 chart = new Highcharts.Chart(";

	private $body = "";

	private $foot = ");</script>";

	private $options = array();

	public function __construct()
	{
		$this->options = array('chart' => array('renderTo' => 'container',
												'backgroundColor' => null));


	}

	public function __call($option, $values)
	{
		if(!empty($values))
		{
			if(is_array($values[0]))
			{
				for($i = 0; $i <= count($values)-1; $i++)
				{
					if(count($values[$i]) == 2)
					{
						$this->options[$option][$values[$i][0]] = $values[$i][1];
					}
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

	public function __get($get)
	{
		switch($get)
		{
			case 'graph': $this->body = json_encode($this->options);
						  return $this->head.$this->body.$this->foot; break;
			default: return false; break;
		}
	}


	public function series($data)
	{

		$this->options['series'] = $data;

/*		switch($typ)
		{
			case 'line': $this->options['series'] = $data; break;
			case 'pie': $this->options['series'] = array(array('type' => 'pie',
											   				   'data' => array('y' => 234,
											   				   				   'name' => 'schlonz'))); break;
			default: return; break;
		}*/
	}




}