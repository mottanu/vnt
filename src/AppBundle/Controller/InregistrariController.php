<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Document\Species;
use AppBundle\Document\Inregistrare;
use AppBundle\Controller\UtilsController;
use AppBundle\Controller\GoogleCloudStorageController;
use Symfony\Component\HttpFoundation\Request;

class InregistrariController extends Controller
{

  /**
   * Matches /v1/inregistrari/add exactly
   *
   * @Route("/v1/inregistrari/add", name="inregistrariAdd")
   * @Method({"POST"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="Adauga inregistrare efective.",
   *     description="Adauga inregistrare efective.",
   *     section="Inregistrari",
   *     parameters={
   *       {"name"="speciesId", "dataType"="string", "required"=true, "source"="body", "description"="Species Id"},
   *       {"name"="nr", "dataType"="integer", "required"=true, "source"="body", "description"="Numar efective"},
   *       {"name"="observatii", "dataType"="string", "required"=false, "source"="body", "description"="Observatii"},
   *       {"name"="alteSpecii", "dataType"="string", "required"=false, "source"="body", "description"="Alte Specii"},
   *       {"name"="stareaTimpului", "dataType"="string", "required"=false, "source"="body", "description"="Starea Timpului"},
   *       {"name"="latitude", "dataType"="string", "required"=false, "source"="body", "description"="Latitude"},
   *       {"name"="longitude", "dataType"="string", "required"=false, "source"="body", "description"="Longitude"},
   *       {"name"="altitude", "dataType"="string", "required"=false, "source"="body", "description"="Altitude"},
   *       {"name"="versionCode", "dataType"="string", "required"=true, "source"="body", "description"="Version code"},
   *       {"name"="timestamp", "dataType"="string", "required"=false, "source"="body", "description"="Timestamp inregistrare online/offline"},
   *       {"name"="detalii[altitudine_minima]", "dataType"="string", "required"=false, "source"="body", "description"="Altitudine minima test"},
   *       {"name"="detalii[altitudine_maxima]", "dataType"="string", "required"=false, "source"="body", "description"="Altitudine maxima test"}
   *     }
   *  )
   */
  public function inregistrariAdd(Request $request) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $userRepository = $dm->getRepository('AppBundle:User');
    $speciesRepository = $dm->getRepository('AppBundle:Species');

    $user = UtilsController::isAuthenticated($request, $dm);

    if(!$user)
      return UtilsController::error("Token invalid!", 404);
    if(!$user->isAgentColector)
      return UtilsController::error("Utilizatorul nu are permisiunea de a realiza aceasta actiune!", 406);

    $versionCode = $this->getParameter('versionCode');
    $userVersionCode =  $request->request->get('versionCode');
    if($versionCode != $userVersionCode)
      return UtilsController::error("Utilizatorul detine o versiune veche de aplicatie. Va rugam actualizati!", 407);

    $speciesId = $request->request->get('speciesId');
    $nr = $request->request->get('nr');
    $observatii = $request->request->get('observatii');
    $alteSpecii = $request->request->get('alteSpecii');
    $stareaTimpului = $request->request->get('stareaTimpului');
    $latitude = $request->request->get('latitude');
    $longitude = $request->request->get('longitude');
    $altitude = $request->request->get('altitude');
    $timestamp = $request->request->get('timestamp');

    if(empty($nr) || !is_numeric($nr))
      return UtilsController::error("Campul Nr nu e un numar valid!", 402);

    if(empty($speciesId))
      return UtilsController::error("Campul id specie este necesar!", 401);
    else {
      $checkSpecies1 = $speciesRepository->findOneById($speciesId);
      $checkSpecies2 = $speciesRepository->findOneBy(array("diviziune.id" => new \MongoId($speciesId)));
      if (!$checkSpecies1 && !$checkSpecies2)
        return UtilsController::error("Id specie invalid!", 402);
    }

    if(empty($timestamp) || !is_numeric($timestamp))
      $timestamp = time();
    
    $detalii = $request->request->get('detalii');
    if($checkSpecies1) {
      $detaliiSpecie = $checkSpecies1->detalii;
    }
    else {
      $detaliiSpecie = $checkSpecies2->detalii;
    }
    
    $detaliiList = array();
    foreach($detaliiSpecie as $detaliu) {
      if(array_key_exists($detaliu['key'], $detalii))
        $detaliiList[$detaliu['key']] = $detalii[$detaliu['key']];
      else
        $detaliiList[$detaliu['key']] = "";
    }

    $newInregistrare = new Inregistrare();
    $newInregistrare->userId = $user->getId();
    $newInregistrare->speciesId = $speciesId;
    $newInregistrare->fondVanatoare = $user->fondVanatoare;
    $newInregistrare->unitateJudeteana = $user->unitateJudeteana;
    $newInregistrare->nr = $nr;
    $newInregistrare->observatii = $observatii;
    $newInregistrare->alteSpecii = $alteSpecii;
    $newInregistrare->stareaTimpului = $stareaTimpului;
    $newInregistrare->latitude = $latitude;
    $newInregistrare->longitude = $longitude;
    $newInregistrare->altitude = $altitude;
    $newInregistrare->versionCode = $versionCode;
    $newInregistrare->created = $newInregistrare->updated = date("c");
    $newInregistrare->dataInregistrare = date("c", $timestamp);
    $newInregistrare->detalii = $detaliiList;
    $dm->persist($newInregistrare);
    $dm->flush();

    $resp = array(
      'ok' => true,
      'result' => array(
        'inregistrare' => $newInregistrare->display()
      )
    );
    return new JsonResponse($resp);
  }

  /**
   * Matches /v1/inregistrari/user/{userId} exactly
   *
   * @Route("/v1/inregistrari/user/{userId}", name="inregistrariUser")
   * @Method({"GET"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="Inregistrari user list.",
   *     description="Inregistrari user.",
   *     section="Inregistrari",
   *     parameters={
   *     }
   *  )
   */
  public function inregistrariUser(Request $request, $userId = null) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isAuthenticated($request, $dm);
    if(!$user)
      return UtilsController::error("Token invalid!", 404);

    if($userId == "me") {
      $userId = $user->getId();
    }

    $inregistrariRepository = $dm->getRepository('AppBundle:Inregistrare');
    $inregistrariCursor = $inregistrariRepository->findBy(array('userId' => $userId));
    $inregistrariList = array();
    foreach($adsCursor as $inregistrare)
      $inregistrariList[] = $inregistrare->display();

    $resp = array(
      'ok' => true,
      'result' => array(
        'inregistrari' => $inregistrariList
      )
    );
    return new JsonResponse($resp);
  }

  /**
   * Matches /v1/inregistrari/{inregistrareId}/addFile exactly
   *
   * @Route("/v1/inregistrari/{inregistrareId}/addFile", name="addFile")
   * @Method({"POST"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="Add file to inregistare.",
   *     description="Add file to inregistare.",
   *     section="Inregistrari",
   *     parameters={
   *       {"name"="file", "dataType"="file", "required"=true, "source"="body", "description"="File"},
   *     }
   *  )
   */
  public function addFile(Request $request, $inregistrareId = null) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isAuthenticated($request, $dm);

    if(!$user)
      return UtilsController::error("Token invalid!", 404);

    $inregistrareRepository = $dm->getRepository('AppBundle:Inregistrare');
    $inregistrare = $inregistrareRepository->findOneById($inregistrareId);
    if (!$inregistrare)
      return UtilsController::error("Nu s-a gasit nicio inregistrare pentru id-ul specificat -> ". $inregistrareId);

    if($user->getId() != $inregistrare->userId)
      return UtilsController::error("Utilizatorul nu are permisiunea de a modifica aceasta inregistrare!", 406);

    $file = $request->files->get('file');
    if($file == null)
      return UtilsController::error("Lipsa fisier!");

    if($file->getSize() > 5000*1024)
      return UtilsController::error("Fisierul folosit e prea mare. Marimea maxima e de 5 MB!");

    if(!is_array(getimagesize($file->getPathname())))
      return UtilsController::error("Tipul de fisier ales nu e acceptat!");

    // if(count($ad->files) >=5)
    //   return UtilsController::error("Only 5 images accepted for one ad!");

    $newFileId = new \MongoId(); $newFileId = $newFileId->__toString();
    $newFilePath = $file->getPathname();
    $newFileName = $user->getId() . '/'. $newFileId . '/' . $file->getClientOriginalName();
    $newFileType = $file->getMimeType();
    $fileUploaded = GoogleCloudStorageController::uploadFile($newFilePath, $newFileName, $newFileType);

    if(is_string($fileUploaded))
      return UtilsController::error("Eroare la uploadul de fisier. Motivul: " . $fileUploaded);

    // Resize image
    $container = $this->container;
    $dataManager = $container->get('liip_imagine.data.manager');
    $filterManager = $container->get('liip_imagine.filter.manager');
    $fullSizeImgWebPath = '/' . $file->getPathname();

    // Resize large image
    $newFileLargePath = "/var/tmp/l" . time();
    $image = $dataManager->find('large_filter', $fullSizeImgWebPath);
    $response = $filterManager->applyFilter($image, 'large_filter');
    $thumb = $response->getContent();
    $f = fopen($newFileLargePath, 'w');
    fwrite($f, $thumb);
    fclose($f);

    // Upload large file
    $newFileLargeName = $user->getId() . '/'. $newFileId . '/large.jpg';
    $fileLargeUploaded = GoogleCloudStorageController::uploadFile($newFileLargePath, $newFileLargeName, $newFileType);
    unlink($newFileLargePath);

    // Resize small image
    $newFileSmallPath = "/var/tmp/s" . time();
    $image = $dataManager->find('small_filter', $fullSizeImgWebPath);
    $response = $filterManager->applyFilter($image, 'small_filter');
    $thumb = $response->getContent();
    $f = fopen($newFileSmallPath, 'w');
    fwrite($f, $thumb);
    fclose($f);

    // Upload large file
    $newFileSmallName = $user->getId() . '/'. $newFileId . '/small.jpg';
    $fileSmallUploaded = GoogleCloudStorageController::uploadFile($newFileSmallPath, $newFileSmallName, $newFileType);
    unlink($newFileSmallPath);

    $imageSize = getimagesize($newFilePath);
    $newFile = array(
      'fileId' => $newFileId,
      'filePath' => $newFileName,
      'fileType' => $newFileType,
      'original' => $fileUploaded->getMediaLink(),
      'small' => $fileSmallUploaded->getMediaLink(),
      'smallPath' => $newFileSmallName,
      'large' => $fileLargeUploaded->getMediaLink(),
      'largePath' => $newFileLargeName,
      'originalWidth' => $imageSize[0],
      'originalHeight' => $imageSize[1]
    );

    $inregistrare->files[] = $newFile;
    $inregistrare->updated = date("c");

    $dm->flush();

    $resp = array(
      'ok' => true,
      'result' => array(
        'inregistrare' => $inregistrare->display()
      )
    );
    return new JsonResponse($resp);
  }

  /**
   * Matches /v1/inregistrari/{inregistrareId}/removeFile exactly
   *
   * @Route("/v1/inregistrari/{inregistrareId}/removeFile", name="removeFile")
   * @Method({"POST"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="Remove file from inregistrare.",
   *     description="Remove file from inregistrare.",
   *     section="Inregistrari",
   *     parameters={
   *       {"name"="fileId", "dataType"="string", "required"=true, "source"="body", "description"="File ID"},
   *     }
   *  )
   */
  public function removeFile(Request $request, $adId = null) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isAuthenticated($request, $dm);

    if(!$user)
      return UtilsController::error("Token invalid!", 404);

    $inregistrareRepository = $dm->getRepository('AppBundle:Inregistrare');
    $inregistrare = $inregistrareRepository->findOneById($inregistrareId);
    if (!$inregistrare)
      return UtilsController::error("Nu am gasit nicio inregistrare pentru id-ul oferit ". $inregistrareId);

    if($user->getId() != $inregistrare->userId)
      return UtilsController::error("Utilizatorul nu are permisiunea de a edita aceasta inregistrare!");

    $fileId = $request->request->get('fileId');

    if(empty($fileId))
      return UtilsController::error("Campul fileId este necesar!");

    $newFiles = array();
    foreach($ad->files as $file) {
      if($file['fileId'] == $fileId) {
        GoogleCloudStorageController::deleteFile($file['filePath']);
        GoogleCloudStorageController::deleteFile($file['smallPath']);
        GoogleCloudStorageController::deleteFile($file['largePath']);
      }
      else
        $newFiles[] = $file;
    }

    $inregistrare->files = $newFiles;
    $dm->flush();

    $resp = array(
      'ok' => true,
      'result' => array(
        'inregistrare' => $inregistrare->display()
      )
    );
    return new JsonResponse($resp);
  }

}
