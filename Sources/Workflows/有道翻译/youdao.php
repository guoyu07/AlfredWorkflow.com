<?php
/**
 * 利用有道翻译 API 接口达到翻译的目的
 *
 * @author icyleaf <icyleaf.cn@gmail.com>
 */
require_once('workflows.php');


class YouDaoTranslation
{
	private $_url = "http://fanyi.youdao.com/openapi.do"; //?keyfrom=$from&key=$key&type=data&doctype=json&version=1.1&q=$q"
	private $_query = null;

	private $_workflow = null;
	private $_data = array();

	public static function factory($from, $key, $q)
	{
		return new YouDaoTranslation($from, $key, $q);
	}

	public function __construct($from, $key, $q)
	{
		$this->_workflow = new Workflows();

		$this->_query = $q;

		$this->_url .= '?' . http_build_query(array(
			'keyfrom'	=> $from,
			'key'		=> $key,
			'type'		=> 'data',
			'doctype'	=> 'json',
			'version'	=> '1.1',
			'q'			=> $q,
			));

		$this->_data = json_decode($this->_workflow->request($this->_url));
	}

	public function postToNotification()
	{
		$response = $this->_data;
		$outputString = "有道翻译也爱莫能助了，你确定翻译的是：'$this->_query' ?";
		if (isset($response->translation) AND isset($response->translation[0]))
		{
			if ($this->_query != str_replace('\\', '', $response->translation[0]))
			{
				echo $response->translation[0]."\n";
				if (isset($response->basic) AND isset($response->basic->explains) AND count($response->basic->explains) > 0)
				{
					foreach ($response->basic->explains as $item) 
					{
						echo $item."\n\r";
					}
				}
			}
		}
	}

	public function listInAlfred()
	{
		$response = $this->_data;
		if (isset($response->translation) AND isset($response->translation[0]))
		{
			$int = 1;
			if ($this->_query != $response->translation[0])
			{
				$translation = str_replace('\\', '', $response->translation[0]);
				if ( ! empty($response->basic->phonetic))
					$translation .= ' [' . $response->basic->phonetic . ']';

				$this->_workflow->result($int.'.'.time(), "$translation", "$translation", '翻译结果', 'icon.png');
			}

			if (isset($response->basic->explains) AND count($response->basic->explains) > 0)
			{
				foreach($response->basic->explains as $item)
				{
					$this->_workflow->result($int.'.'.time(), "$item", "$item", '简明释义', 'icon.png');
					$int++;
				}
			}

			if (isset($response->web) AND count($response->web) > 0)
			{
				foreach($response->web as $item)
				{
					$values = implode(', ', $item->value);
					$this->_workflow->result($int.'.'.time(), "$values", "$values", "网络释义：$item->key", 'icon.png');
					$int++;
				}
			} 
		}

		$results = $this->_workflow->results();
		if (count($results) == 0)
			$this->_workflow->result('youdao', $this->_query, '有道翻译也爱莫能助了', '有道翻译也爱莫能助了，尝试网站搜索: '.$this->_query, 'icon.png' );

		return $this->_workflow->toxml();
	}
}
