<?php
/**
 * Provides a service to simplify communication with the the Brewery DB API.
 *
 * @see    http://brewerydb.com/api/documentation
 * @author Garrison Locke - http://broken-notebook.com - @gplocke
 *
 */
class Bn_Service_Brewerydb
{
    /**
     * Base URL for the Brewerydb API
     *
     * @var string
     */
    const BASE_URL = 'http://brewerydb.com/api';

    /**
     * API key
     *
     * @var string
     */
    protected $_apiKey = '';

    /**
     * Response format
     *
     * @var string
     */
    protected $_format = 'json';

    /**
     * Stores the last parsed response from the server
     *
     * @var stdClass
     */
    protected $_lastParsedResponse = null;

    /**
     * Stores the last raw response from the server
     *
     * @var string
     */
    protected $_lastRawResponse = null;

    /**
     * Stores the last requested URI
     *
     * @var string
     */
    protected $_lastRequestUri = null;

    /**
     * Constructor
     *
     * @param string $apiKey Brewerydb API key
     */
    public function __construct($apiKey)
    {
        $this->_apiKey = (string) $apiKey;
    }

    /**
     * Returns a list of breweries with the given criteria
     *
     * @param int $page The page number to get (results are returned 50 at a time)
     * @param bool $metadata Whether or not to return metadata about the brewery
     * @param int $since Only return breweries created since the given date
     *                   requires [UTC date in YYYY-MM-DD format]
     *
     * @throws Bn_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getBreweries($page = 1, $metadata = true, $since = null, $geo = false, $lat = null, $lng = null, $radius = 50, $units = 'miles')
    {
        if ($geo == true) {
            if (is_null($lat) || is_null($lng)) {
                require_once 'Bn/Service/Brewerydb/Exception.php';
                throw new Bn_Service_Brewerydb_Exception('If doing a geo search, lat and lng values are required');
            }
        }

        $args = array(
            'page'     => $page,
            'metadata' => $metadata
        );

        if (!is_null($since)) {
            $args['since'] = $since;
        }

        if ($geo == true) {
            $args['geo']    = 1;
            $args['lat']    = $lat;
            $args['lng']    = $lng;
            $args['radius'] = $radius;
            $args['units']  = $units;
        }

        return $this->_request('breweries', $args);
    }

    /**
     * Returns info about a single brewery
     *
     * @param int $breweryId The id of the brewery to return
     * @param bool $metadata Whether or not to return metadata about the brewery
     *
     *
     * @throws Bn_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getBrewery($breweryId = 1, $metadata = true)
    {

        $args = array(
            'metadata'  => $metadata
        );

        return $this->_request('breweries/' . $breweryId, $args);
    }


    /**
     * Returns the list of beer for a given brewery ID
     *
     * @param int $breweryId The id of the brewery to get the beers for
     * @param int $page The page number to get (results are returned 50 at a time)
     * @param bool $metadata Whether or not to return metadata about the brewery
     * @param int $since Only return breweries created since the given date
     *
     * @throws Bn_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getBeersForBrewery($breweryId, $page = 1, $metadata = true, $since = null)
    {
        $args = array(
            'brewery_id' => $breweryId,
            'page'       => $page,
            'metadata'   => $metadata
        );

        if (!is_null($since)) {
            $args['since'] = $since;
        }

        return $this->_request('beers', $args);
    }

    /**
     * Returns a list of beers
     *
     * @param int $page The page number to get (results are returned 50 at a time)
     * @param bool $metadata Whether or not to return metadata about the brewery
     * @param int $since Only return breweries created since the given date
     *
     * @throws Bn_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getAllBeers($page = 1, $metadata = true, $since = null)
    {
        $args = array(
            'page'       => $page,
            'metadata'   => $metadata
        );

        if (!is_null($since)) {
            $args['since'] = $since;
        }

        return $this->_request('beers', $args);
    }

    /**
     * Returns the list of all beer styles
     *
     * @throws Bn_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getAllStyles()
    {
        $args = array();

        return $this->_request('styles', $args);
    }


    /**
     * Returns a single beer style
     *
     * @param int $styleId The id of the style to get
     *
     * @throws Bn_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getStyle($styleId)
    {
        $args = array();

        return $this->_request('styles/' . $styleId, $args);
    }

    /**
     * Returns the list of all beer categories
     *
     * @throws Bn_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getAllCategories()
    {
        $args = array();

        return $this->_request('categories', $args);
    }


    /**
     * Returns a single beer category
     *
     * @param int $categoryId The id of the category to get
     *
     * @throws Bn_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getCategory($categoryId)
    {
        $args = array();

        return $this->_request('categories/' . $categoryId, $args);
    }


    /**
     * Returns the list of all types of glassware
     *
     * @throws Bn_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getAllGlassware()
    {
        $args = array();

        return $this->_request('glassware', $args);
    }

    /**
     * Returns info about a single type of glassware
     *
     * @param int $glasswareId The id of the glassware to get
     *
     * @throws Bn_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function getGlassware($glasswareId)
    {
        $args = array();

        return $this->_request('glassware/' . $glasswareId, $args);
    }

    /**
     * Searches the api for the given query
     *
     * @param int $query The query string to search for
     *
     * @throws Bn_Service_Brewerydb_Exception
     *
     * @return stdClass object from the request
     *
     */
    public function search($query, $type = '', $metadata = true, $page = 1)
    {

        $type = strtolower($type);

        if ($type != '' || $type != 'beer' || $type != 'brewery') {
            require_once 'Bn/Service/Brewerydb/Exception.php';
            throw new Bn_Service_Brewerydb_Exception('Type must be either "beer", "brewery", or empty');
        }

        $args = array(
            'q'        => $query,
            'page'     => $page,
            'metadata' => $metadata
        );

        if ($type != '') {
            $args['type'] = $type;
        }

        return $this->_request('search/', $args);
    }


    /**
     * Sends a request using curl to the required endpoint
     *
     * @param string $endpoint The BreweryDb endpoint to use
     * @param array $args key value array of arguments
     *
     * @throws Bn_Service_Brewerydb_Exception
     *
     * @return stdClass object
     */
    protected function _request($endpoint, $args)
    {
        $this->_lastRequestUri = null;
        $this->_lastRawResponse = null;
        $this->_lastParsedResponse = null;

        // Append the API key to the args passed in the query string
        $args['apikey'] = $this->_apiKey;
        $args['format'] = $this->_format;


        // Clean up the empty args so they'll return the API's default
        foreach ($args as $key => $value) {
            if ($value == '') {
                unset($args[$key]);
            }
        }

        $this->_lastRequestUri = self::BASE_URL . '/' . $endpoint . '/?' . http_build_query($args);

        // Set curl options and execute the request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_lastRequestUri);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $this->_lastRawResponse = curl_exec($ch);

        if ($this->_lastRawResponse === false) {

            $this->_lastRawResponse = curl_error($ch);
            require_once 'Bn/Service/Brewerydb/Exception.php';
            throw new Bn_Service_Brewerydb_Exception('CURL Error: ' . curl_error($ch));
        }

        curl_close($ch);

        // Response comes back as either JSON or XML, so we decode it into a stdClass object
        if ($args['format'] == 'xml') {
            // we only support json, so this shouldn't ever happen, but maybe
            // in the future we'll let it return XML and treat it differently.
            // JSON is just way simpler to mess with.
        } else {
            $this->_lastParsedResponse = json_decode($this->_lastRawResponse);
        }

        // If the http_code var is not found, the response from the server was unparsable
        /*if (isset($this->_lastParsedResponse->error)) {
            require_once 'Bn/Service/Brewerydb/Exception.php';
            throw new Bn_Service_Brewerydb_Exception('Error parsing response from server.');
        }*/

        // Server provides error messages in http_code and error vars.  If not 200, we have an error.
        if (isset($this->_lastParsedResponse->error)) {
            require_once 'Bn/Service/Brewerydb/Exception.php';
            throw new Bn_Service_Brewerydb_Exception('Brewerydb Service Error: ' .
                    $this->_lastParsedResponse->error->message);
        }

        return $this->getLastParsedResponse();
    }

    /**
     * Gets the last parsed response from the service
     *
     * @return null|stdClass object
     */
    public function getLastParsedResponse()
    {
        return $this->_lastParsedResponse;
    }

    /**
     * Gets the last raw response from the service
     *
     * @return null|json string
     */
    public function getLastRawResponse()
    {
        return $this->_lastRawResponse;
    }

    /**
     * Gets the last request URI sent to the service
     *
     * @return null|string
     */
    public function getLastRequestUri()
    {
        return $this->_lastRequestUri;
    }
}