<?php

  // NOTE: As of Jan 4th 2015, Virginia Tech Web Hosting uses PHP Version: 5.2.16
  
  date_default_timezone_set('America/New_York');

  /**
   * Connects to HokiePRIDE's database.
   *
   * @return The connection to the database if successful. Null otherwise.
   */
  function connectToDB()
  {  
    // include database credentials for successful connection.
    include('dbconfig.php');

    // create connection
    $mysqli = new mysqli($dbhost, $dbuser, $dbpassword, $dbname);

    // PHP Website says OO version of connect_error is broken until PHP Version 5.3.0. 
    // Use procedural version instead.
    //if ($mysqli->connect_error())
    if (mysqli_connect_error($mysqli))
    {
      $message = date("Y-m-d H:i:s").' failed connect to database. '.$mysqli->connect_errno($mysqli).': '.mysqli_connect_error($mysqli);
      error_log($message, 3, 'logs/general.log');
      return NULL;
    }
    else
    {
      $message = date("Y-m-d H:i:s").' successful connect to database.'."\n";
      error_log($message, 3, 'logs/general.log');
      return $mysqli;
    }
  }

  /**
   * Disconnects from HokiePRIDE's database. This function may not be necessary since
   * connectToDB returns its connection to the database or null but perhaps this would be helpful for 
   * syntax and readability purposes.
   *
   * @param $mysqli a reference to a previously and successfully connected database.
   *
   * @return NULL in all cases.
   */
  function disconnectFromDB($mysqli = NULL)
  {
    if (!is_null($mysqli))
    {
      //$database->disconnect();
      //mysqli_close($mysqli);
      $mysqli->close();
      $message = date("Y-m-d H:i:s").' successful disconnect from database.'."\n";
      error_log($message, 3, 'logs/general.log');
    }
    else
    {
      $message = date("Y-m-d H:i:s").' attempting to disconnect from null database.'."\n";
      error_log($message, 3, 'logs/general.log');
    }
  }
?>