<?
class SapeApiException extends Exception {}

/**
 * SapeApi
 *
 * интерфейс для работы с xml-rpc sape.ru
 * потребуется библиотека xml-rpc для php http://sourceforge.net/projects/phpxmlrpc/files/phpxmlrpc/3.0.0beta/xmlrpc-3.0.0.beta.zip/download
 *
 * пример:
 * require_once './3rdparty/xmlrpc-3.0.0.beta/lib/xmlrpc.inc';
 * require_once 'sape_api.class.php';
 * $sape_xml = new SapeApi;
 * $connect = $sape_xml->set_debug(0)->connect();
 * $get_user = $connect->query('sape.get_user')->exec(); // метод без аргументов
 * $get_site_pages = $connect->query('sape.get_site_pages', 88888, 111)->exec();
 *
 * @author Frenk1 aka Gudd.ini,
 * @version 0.1
 * @link http://phpxmlrpc.sourceforge.net/doc-2/
 */
class SapeApi
{
	/**
	 * Свойства с данными для соединения с сервером xml-rpc
	 */
	protected $_path = '/xmlrpc/?v=extended';
	protected $_host = 'api.sape.ru';
	protected $_port = 80;

	/**
	 * Уровень режима отладки (0, 1, 2)
	 */
	private $_debug = 0;

	/**
	 * Объект текущего соединения
	 * @link http://phpxmlrpc.sourceforge.net/doc-2/
	 * @var	xmlrpc_client
	 */
	private $_xmlrpc = false;

	/**
	 * текущие куки
	 */
	private $_cookies = false;

	/**
	 * Результат выполнения последнего запроса
	 * @link http://phpxmlrpc.sourceforge.net/doc-1.1/ch05s03.html
	 * @var xmlrpcresp
	 */
	private $_response = false;

	/**
	 * Последний сохраненный запрос
	 */
	private $_query = false;

	/**
	 * Ошибка выполнения запроса
	 * @var string
	 */
	private $_error = '';

	/**
	 * Номер ошибки
	 * @var integer
	 */
	private $_errnum = 0;

	/**
	 * Подключение к серверу и поддержка соединения
	 * @param	string	$user	username SAPE
	 * @param	string	$pass	password SAPE
	 * @throws SapeApiException
	 * @return SapeApi
	 * @link http://phpxmlrpc.sourceforge.net/doc-2/
	 */
	function connect($user, $pass)
	{
		$this->_xmlrpc = new xmlrpc_client(
			$this->_path, $this->_host, $this->_port
		);
		$this->_xmlrpc->setDebug($this->_debug);

		$query = new xmlrpcmsg(
				'sape.login',
				array(
					php_xmlrpc_encode($user),
					php_xmlrpc_encode(md5($pass)),
					php_xmlrpc_encode(true)
				)
		);
		$this->_response = $this->_xmlrpc->send($query);

		try {
			if ( !$this->_response->value()->scalarval() ) {
				throw new SapeApiException('Не пришел user_id от сервера');
			}

		} catch (SapeApiException $e) {
			echo $e->getMessage();
		}

		return $this;
	}

	/**
	 * Установить уровень отладки
	 * @return SapeApi
	 */
	public function set_debug($lvl = NULL)
	{
		if (!is_null($lvl)) {
			$lvl = intval($lvl);
			$this->_debug = $lvl;
		}

		return $this;
	}

	/**
	 * Cгенерировать запрос
	 * @param	string	$sape_method	метод запроса
	 * @param	mix		$arg1	аргумент
	 * @param	mix		$arg2	аргумент
	 * @param	mix		$argN	аргумент N
	 * @return SapeApi
	 * @link http://phpxmlrpc.sourceforge.net/doc-2/
	 */
	public function query($sape_method, $arg1 = null, $arg2 = null, $argN = null)
	{
		$args = array_slice(func_get_args(), 1);

		if (count($args)) {
			foreach ($args as $arg) {
				$sape_args[] = php_xmlrpc_encode($arg);
			}
			$this->_query = new xmlrpcmsg($sape_method, $sape_args);
		} else {
			$this->_query = new xmlrpcmsg($sape_method);
		}

		return $this;
	}

	/**
	 * Выполнить запрос
	 * @link http://phpxmlrpc.sourceforge.net/doc-1.1/ch05s03.html
	 * @return xmlrpcresp	Methods: faultCode(), faultString(), value(), etc..
	 */
	public function exec()
	{
		try {
			if ( ! $this->_query) {
				throw new SapeApiException('Нет запроса для выполнения');
			}
		} catch (SapeApiException $e) {
			echo $e->getMessage();
		}

		$this->_sync_cookies();

		$this->_response = $this->_xmlrpc->send($this->_query);

		try {
			if ($this->_response->faultCode()) {
				throw new SapeApiException('Сервер sape сообщил об ошибке');
			}

		} catch (SapeApiException $e) {
			echo $e->getMessage();
		}

		return $this->_response;
	}

	/**
	 * Выполнить запрос, извлечь данные, обработать ошибки
	 * @return array
	 */
	public function fetch()
	{
		$response = $this->exec();
		if ($response->faultCode()) {
			$this->_set_errnum($response->faultCode());
			$this->_set_error($response->faultString());
			return false;
		}

		return php_xmlrpc_decode($response->value());
	}

	/**
	 * получение свежих кук от сервера
	 */
	function get_cookies() {
		$this->_cookies = $this->_response->_cookies;
	}

	/**
	 * Синхронизировать куки (при каждом запросе)
	 * @return void
	 */
	private function _sync_cookies()
	{
		$this->get_cookies();
		foreach ($this->_response->_cookies as $name => $value) {
			$this->_xmlrpc->setCookie($name, $value['value']);
		}
	}

	/**
	 * Получить результат выполненного запроса
	 * @return xmlrpcresp
	 */
	public function get_response()
	{
		return $this->_response;
	}

	private function _set_error($error) {
		$this->_error = $error;
	}

	public function get_error() {
		return $this->_error;
	}

	public function get_errnum() {
		return $this->_errnum;
	}

	private function _set_errnum($errnum) {
		$this->_errnum = $errnum;
	}

}

