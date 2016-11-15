<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;


require_once __DIR__. '/../../../vendor/autoload.php';

class GoogleCloudStorageController extends Controller
{
      
  /**
   * Upload file to Google Cloud Storage
   */
  public function uploadFile($filePath, $fileName, $fileType) {
    
    $bucket			= "staging.mm-vanatoare.appspot.com";
    $client = new \Google_Client();
    $client->setAuthConfig(__DIR__ . '/MM-Vanatoare-b2a1f1acbc2a.json'); 
    $client->setScopes(array('https://www.googleapis.com/auth/devstorage.read_write'));
    $storageService = new \Google_Service_Storage( $client );
    
    /**
     * Write file to Google Storage
     */
    try 
    {
      $readerAccess = new \Google_Service_Storage_ObjectAccessControl();
      $readerAccess->setEntity('allUsers');
      $readerAccess->setRole('READER');
      
    	$postbody = array( 
    			'name' => $fileName, 
    			'data' => file_get_contents($filePath),
    			'uploadType' => "media",
          'mimeType' => $fileType
    			);
    	$gsso = new \Google_Service_Storage_StorageObject();
    	$gsso->setName($fileName);
      $gsso->setAcl([$readerAccess]);
    	$result = $storageService->objects->insert( $bucket, $gsso, $postbody );
    	
      return $result;
    }      
    catch (Exception $e)
    {
      return $e->getMessage();
    }
          
  }
  
  /**
   * Delete file from Google Cloud Storage
   */
  public function deleteFile($filePath) {
    
    $bucket			= "staging.mm-vanatoare.appspot.com";
    $client = new \Google_Client();
    $client->setAuthConfig(__DIR__ . '/MM-Vanatoare-b2a1f1acbc2a.json'); 
    $client->setScopes(array('https://www.googleapis.com/auth/devstorage.read_write'));
    $storageService = new \Google_Service_Storage( $client );
    
    /**
     * Delete file from Google Storage
     */
    try 
    {     
      $storageService->objects->delete($bucket, $filePath);
    }      
    catch (Exception $e)
    {
      
    }
          
  }
}
