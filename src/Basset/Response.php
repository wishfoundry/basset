<?php namespace Basset;

use Illuminate\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Config\Repository;
use Illuminate\Http\Response as HttpResponse;

class Response {

	/**
	 * Filesystem instance.
	 * 
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * Request instance.
	 * 
	 * @var Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * Config repository instance.
	 * 
	 * @var Illuminate\Config\Repository
	 */
	protected $config;

	/**
	 * Response instance.
	 * 
	 * @var Illuminate\Http\Response
	 */
	protected $response;

	/**
	 * Create a new response instance.
	 * 
	 * @param  Illuminate\Http\Request  $request
	 * @param  Illuminate\Filesystem  $files
	 * @param  Illuminate\Config\Repository  $config
	 * @return void
	 */
	public function __construct(Request $request, Filesystem $files, Repository $config)
	{
		$this->files = $files;
		$this->request = $request;
		$this->config = $config;
	}

	/**
	 * Verify that the request is intended for an asset.
	 * 
	 * @return bool
	 */
	public function verifyRequest()
	{
		$handles = $this->config['basset.handles'];

		return str_is("{$handles}/*", trim($this->request->getRequestUri(), '/'));
	}

	/**
	 * Prepares the response before sending to the browser.
	 * 
	 * @return Illuminate\Http\Response
	 */
	public function prepare()
	{
		$collection = new Collection(null, $this->files, $this->config);

		$asset = $this->getAssetFromUri($this->request->getRequestUri());

		if ( ! $asset = $collection->add($asset))
		{
			return;
		}

		// Create a new HttpResponse object with the contents of the asset. Once we have the
		// response object we can adjust the headers before sending it to the browser.
		$this->response = new HttpResponse($collection->compile($asset->getGroup()));

		switch ($asset->getGroup())
		{
			case 'style':
				$this->response->headers->set('content-type', 'text/css');
				break;
			case 'script':
				$this->response->headers->set('content-type', 'application/javascript');
				break;
		}

		return $this;
	}

	/**
	 * Get the response object.
	 * 
	 * @return string
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Get the asset from the URI.
	 * 
	 * @param  string  $uri
	 * @return string
	 */
	protected function getAssetFromUri($uri)
	{
		$pieces = explode('/', str_replace($this->request->getBaseUrl(), '', $uri));

		unset($pieces[array_search($this->config['basset.handles'], $pieces)]);

		return implode('/', array_filter($pieces));
	}

}