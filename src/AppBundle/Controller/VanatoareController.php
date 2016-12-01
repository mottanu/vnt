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

class VanatoareController extends Controller
{

  /**
   * Matches /v1/vanatoare/checkin exactly
   *
   * @Route("/v1/vanatoare/checkin ", name="vanatoareCheckin")
   * @Method({"POST"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="Checkin autorizatie.",
   *     description="Checkin autorizatie.",
   *     section="Vanatoare",
   *     parameters={
   *       {"name"="numarAutorizatie", "dataType"="string", "required"=true, "source"="body", "description"="Numar autorizatie"}
   *     }
   *  )
   */
  public function vanatoareCheckin(Request $request) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $userRepository = $dm->getRepository('AppBundle:User');
    $speciesRepository = $dm->getRepository('AppBundle:Species');
    $autorizatiiRepository = $dm->getRepository('AppBundle:Autorizatie');

    $user = UtilsController::isAuthenticated($request, $dm);

    if(!$user)
      return UtilsController::error("Token invalid!", 404);
    if(!$user->isAgentColector && !$user->isAgentFondVanatoare)
      return UtilsController::error("Utilizatorul nu are permisiunea de a realiza aceasta actiune!", 406);

    $numarAutorizatie = $request->request->get('numarAutorizatie');
    if(empty($numarAutorizatie))
      return UtilsController::error("Campul numarAutorizatie lipseste!", 411);
    
    $autorizatie =  $autorizatiiRepository->findOneByNumar($numarAutorizatie);
    if(!$autorizatie)
      return UtilsController::error("Numarul autorizatiei este invalid!", 412);
    
    if(!$autorizatie->activa)
      return UtilsController::error("Autorizatia nu este valabila!", 413);
    
    if($autorizatie->organizatorId != $user->getId())
      return UtilsController::error("Utilizatorul nu are permisiunea de a utiliza aceasta autorizatie!", 414);

    $autorizatie->checkinData = date("c");
    $autorizatie->checkinUser = $user->getId();
    $dm->flush();

    $resp = array(
      'ok' => true,
      'result' => array(
        'autorizatie' => $autorizatie->display()
      )
    );
    return new JsonResponse($resp);
  }
  
  /**
   * Matches /v1/vanatoare/{vanatoareId}/adaugaVanatori exactly
   *
   * @Route("/v1/inregistrari/{vanatoareId}/adaugaVanatori", name="vanatoareAutorizatieVanatori")
   * @Method({"POST"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="Adauga vanatori.",
   *     description="Adauga vanatori.",
   *     section="Vanatoare",
   *     parameters={
   *       {"name"="vanator", "dataType"="text", "required"=true, "source"="body", "description"="Vanator"},
   *     }
   *  )
   */
  public function vanatoareAutorizatieVanatori(Request $request, $vanatoareId = null) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $userRepository = $dm->getRepository('AppBundle:User');
    $autorizatiiRepository = $dm->getRepository('AppBundle:Autorizatie');

    $user = UtilsController::isAuthenticated($request, $dm);

    if(!$user)
      return UtilsController::error("Token invalid!", 404);
    if(!$user->isAgentColector && !$user->isAgentFondVanatoare)
      return UtilsController::error("Utilizatorul nu are permisiunea de a realiza aceasta actiune!", 406);

    if(empty($vanatoareId))
      return UtilsController::error("Autorizatie invalida!", 414);
    
    $autorizatie =  $autorizatiiRepository->findOneById($vanatoareId);
    if(!$autorizatie)
      return UtilsController::error("Autorizatie invalida!", 414);
    
    if(!$autorizatie->activa)
      return UtilsController::error("Autorizatia nu este valabila!", 413);
    
    if($autorizatie->organizatorId != $user->getId())
      return UtilsController::error("Utilizatorul nu are permisiunea de a utiliza aceasta autorizatie!", 414);
    
    if($autorizatie->tip == 0)
      return UtilsController::error("Autorizatie este individuala. Nu se pot adauga vanatori!", 415);
    
    if($autorizatie->tip == 1 && count($autorizatie->vanatori) == 5)
      return UtilsController::error("Autorizatie este de tip grup restrans. Nu se mai pot adauga alti vanatori!", 416);
    
    if($autorizatie->tip == 2 && count($autorizatie->vanatori) == 25)
      return UtilsController::error("Autorizatie este de tip colectiva. Nu se mai pot adauga alti vanatori!", 417);
    
    $vanatorNou = $request->request->get("vanator");

    $autorizatie->vanatori[] = $vanatorNou;
    $dm->flush();

    $resp = array(
      'ok' => true,
      'result' => array(
        'autorizatie' => $autorizatie->display()
      )
    );
    return new JsonResponse($resp);
  }

  /**
   * Matches /v1/vanatoare/{vanatoareId}/adaugaVanat exactly
   *
   * @Route("/v1/vanatoare/{vanatoareId}/adaugaVanat", name="vanatoareAutorizatieVanat")
   * @Method({"POST"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="Adauga vanat.",
   *     description="Adauga vanat.",
   *     section="Vanatoare",
   *     parameters={
   *       {"name"="file", "dataType"="file", "required"=true, "source"="body", "description"="File"},
   *       {"name"="vanator", "dataType"="text", "required"=true, "source"="body", "description"="Vanator"},
   *       {"name"="speciesId", "dataType"="text", "required"=true, "source"="body", "description"="Id Specie"},
   *       {"name"="nr", "dataType"="text", "required"=true, "source"="body", "description"="Numar animale vanate"},
   *       {"name"="serieCrotal", "dataType"="text", "required"=true, "source"="body", "description"="Serii crotal"}
   *     }
   *  )
   */
  public function vanatoareAutorizatieVanat(Request $request, $vanatoareId = null) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $userRepository = $dm->getRepository('AppBundle:User');
    $autorizatiiRepository = $dm->getRepository('AppBundle:Autorizatie');

    $user = UtilsController::isAuthenticated($request, $dm);

    if(!$user)
      return UtilsController::error("Token invalid!", 404);
    if(!$user->isAgentColector && !$user->isAgentFondVanatoare)
      return UtilsController::error("Utilizatorul nu are permisiunea de a realiza aceasta actiune!", 406);

    if(empty($vanatoareId))
      return UtilsController::error("Autorizatie invalida!", 414);
    
    $autorizatie =  $autorizatiiRepository->findOneById($vanatoareId);
    if(!$autorizatie)
      return UtilsController::error("Autorizatie invalida!", 414);
    
    if(!$autorizatie->activa)
      return UtilsController::error("Autorizatia nu este valabila!", 413);
    
    if($autorizatie->organizatorId != $user->getId())
      return UtilsController::error("Utilizatorul nu are permisiunea de a utiliza aceasta autorizatie!", 414);
    
    $vanator = $request->request->get("vanator");
    $speciesId = $request->request->get("speciesId");
    $nr = $request->request->get("nr");
    $serieCrotal = $request->request->get("serieCrotal");
    
    if(empty($vanator))
      return UtilsController::error("Campul vanator nu este valid!", 418);
    
    if(empty($speciesId))
      return UtilsController::error("Campul speciesId nu este valid!", 419);
    
    if(empty($nr) || !is_numeric($nr))
      return UtilsController::error("Campul Nr nu e un numar valid!", 420);

    $newFile = null;
    $file = $request->files->get('file');
    if($file != null) {
      if($file->getSize() > 5000*1024)
        return UtilsController::error("Fisierul folosit e prea mare. Marimea maxima e de 5 MB!");

      if(!is_array(getimagesize($file->getPathname())))
        return UtilsController::error("Tipul de fisier ales nu e acceptat!");
      
      $newFileId = new \MongoId(); $newFileId = $newFileId->__toString();
      $newFilePath = $file->getPathname();
      $newFileName = $user->getId() . '/'. $newFileId . '/' . $file->getClientOriginalName();
      $newFileType = $file->getMimeType();
      $fileUploaded = GoogleCloudStorageController::uploadFile($newFilePath, $newFileName, $newFileType);
      
      if(!is_string($fileUploaded)) {
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
      }
    }
      
    $autorizatie->vanat[] = array(
      'vanator' => $vanator,
      'speciesId' => $speciesId,
      'serieCrotal' => $serieCrotal,
      'nr' => $nr,
      'file' => $newFile,
      'data' => date('c')
    ); 

    $dm->flush();

    $resp = array(
      'ok' => true,
      'result' => array(
        'autorizatie' => $autorizatie->display()
      )
    );
    return new JsonResponse($resp);
  }
  
  /**
   * Matches /v1/vanatoare/{vanatoareId}/checkout exactly
   *
   * @Route("/v1/inregistrari/{vanatoareId}/checkout", name="vanatoareAutorizatieCheckout")
   * @Method({"POST"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="Checkout vanatoare.",
   *     description="Checkout vanatoare.",
   *     section="Vanatoare",
   *     parameters={
   *     }
   *  )
   */
  public function vanatoareAutorizatieCheckout(Request $request, $vanatoareId = null) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $userRepository = $dm->getRepository('AppBundle:User');
    $autorizatiiRepository = $dm->getRepository('AppBundle:Autorizatie');

    $user = UtilsController::isAuthenticated($request, $dm);

    if(!$user)
      return UtilsController::error("Token invalid!", 404);
    if(!$user->isAgentColector && !$user->isAgentFondVanatoare)
      return UtilsController::error("Utilizatorul nu are permisiunea de a realiza aceasta actiune!", 406);

    if(empty($vanatoareId))
      return UtilsController::error("Autorizatie invalida!", 414);
    
    $autorizatie =  $autorizatiiRepository->findOneById($vanatoareId);
    if(!$autorizatie)
      return UtilsController::error("Autorizatie invalida!", 414);
    
    if(!$autorizatie->activa)
      return UtilsController::error("Autorizatia nu este valabila!", 413);
    
    if($autorizatie->organizatorId != $user->getId())
      return UtilsController::error("Utilizatorul nu are permisiunea de a utiliza aceasta autorizatie!", 414);

    $autorizatie->activa = false;
    $autorizatie->checkoutData = date("c");
    $dm->flush();

    $resp = array(
      'ok' => true,
      'result' => array(
        'autorizatie' => $autorizatie->display()
      )
    );
    return new JsonResponse($resp);
  }
  
  // /**
  //  * Matches /v1/inregistrari/{inregistrareId}/removeFile exactly
  //  *
  //  * @Route("/v1/inregistrari/{inregistrareId}/removeFile", name="removeFile")
  //  * @Method({"POST"})
  //  *
  //  * @ApiDoc(
  //  *     resource=true,
  //  *     resourceDescription="Remove file from inregistrare.",
  //  *     description="Remove file from inregistrare.",
  //  *     section="Inregistrari",
  //  *     parameters={
  //  *       {"name"="fileId", "dataType"="string", "required"=true, "source"="body", "description"="File ID"},
  //  *     }
  //  *  )
  //  */
  // public function removeFile(Request $request, $adId = null) {
  //
  //   $dm = $this->get('doctrine_mongodb')->getManager();
  //   $user = UtilsController::isAuthenticated($request, $dm);
  //
  //   if(!$user)
  //     return UtilsController::error("Token invalid!", 404);
  //
  //   $inregistrareRepository = $dm->getRepository('AppBundle:Inregistrare');
  //   $inregistrare = $inregistrareRepository->findOneById($inregistrareId);
  //   if (!$inregistrare)
  //     return UtilsController::error("Nu am gasit nicio inregistrare pentru id-ul oferit ". $inregistrareId);
  //
  //   if($user->getId() != $inregistrare->userId)
  //     return UtilsController::error("Utilizatorul nu are permisiunea de a edita aceasta inregistrare!");
  //
  //   $fileId = $request->request->get('fileId');
  //
  //   if(empty($fileId))
  //     return UtilsController::error("Campul fileId este necesar!");
  //
  //   $newFiles = array();
  //   foreach($ad->files as $file) {
  //     if($file['fileId'] == $fileId) {
  //       GoogleCloudStorageController::deleteFile($file['filePath']);
  //       GoogleCloudStorageController::deleteFile($file['smallPath']);
  //       GoogleCloudStorageController::deleteFile($file['largePath']);
  //     }
  //     else
  //       $newFiles[] = $file;
  //   }
  //
  //   $inregistrare->files = $newFiles;
  //   $dm->flush();
  //
  //   $resp = array(
  //     'ok' => true,
  //     'result' => array(
  //       'inregistrare' => $inregistrare->display()
  //     )
  //   );
  //   return new JsonResponse($resp);
  // }

}
