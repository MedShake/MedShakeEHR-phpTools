<?php
/*
 * This file is part of MedShakeEHR.
 *
 * Copyright (c) 2019
 * Bertrand Boutillier <b.boutillier@gmail.com>
 * http://www.medshake.net
 *
 * MedShakeEHR is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * MedShakeEHR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MedShakeEHR.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Import VillagePeople
 * Importer le CSV obtenu par le script MedShake VillagePeople
 * (génération d'une population aléatoire)
 *
 * @author Bertrand Boutillier <b.boutillier@gmail.com>
 */

ini_set('display_errors', 1);

/////////// Composer class auto-upload
require '../vendor/autoload.php';

$homepath=getenv("MEDSHAKEEHRPATH");
spl_autoload_register(function ($class) {
    global $homepath;
    if (is_file($homepath.'class/' . $class . '.php')) {
        include $homepath.'class/' . $class . '.php';
    }
});

/////////// Config loader
$p['config']=Spyc::YAMLLoad($homepath.'config/config.yml');

/////////// correction pour host non présent (IP qui change)
if ($p['config']['host']=='') {
    $p['config']['host']=$_SERVER['SERVER_ADDR'];
    $p['config']['cookieDomain']=$_SERVER['SERVER_ADDR'];
}
$p['homepath']=$homepath;

/////////// SQL connexion
$mysqli=msSQL::sqlConnect();


$csvFile = file('export.csv');
foreach ($csvFile as $line) {
  $d = str_getcsv($line, ';');
  if(!is_numeric($d[0])) continue;

  $newpatient = new msPeopleRelations();
  $newpatient->setFromID('3');
  $newpatient->setType('patient');
  $newpatient->createNew($d[0]);
}
unset($csvFile);

$csvFile = file('export.csv');

foreach ($csvFile as $line) {
    $d = str_getcsv($line, ';');
    if(!is_numeric($d[0])) continue;

    echo "Adding client with id ". $d[0];

    $patient = new msPeopleRelations();
    $patient->setFromID('3');
    $patient->setToID($d[0]);
    $patient->setType('patient');

    $pd = new msObjet();
    $pd->setFromID('3');
    $pd->setToID($d[0]);

    $date = DateTime::createFromFormat('Y-m-d', $d[4]);
    $d['4']=$date->format('d/m/Y');
    $d['8']=strtoupper($d['8']);
    // data base
    $cor=array(
      '1'=>'firstname',
      '2'=>'birthname',
      '3'=>'lastname',
      '4'=>'birthdate',
      '6'=>'weight',
      '7'=>'height',
      '8'=>'administrativeGenderCode',
      '15'=>'job',
      '16'=>'streetNumber',
      '17'=>'street',
      '18'=>'city',
      '19'=>'postalCodePerso',
      '20'=>'mobilePhone',
      '21'=>'homePhone',
      '6'=>'poids',
      '7'=>'taillePatient',
      '22'=>'personalEmail'
    );

    foreach($cor as $k=>$v) {
        if(!empty($cor[$k])) {
          $pd->createNewObjetByTypeName($v, $d[$k]);
        }
    }

    // conjoint
    if($d[13] > 0) {
        $patient->setRelationType('relationPatientPatient');
        $patient->setWithId(intval($d[13]));
        $patient->setToStatus('conjoint');
        $patient->setRelation();
      }

      // enfants
      if(!empty($d[11])) {
        foreach(explode(',', $d[11]) as $k=>$v) {
          $patient->setRelationType('relationPatientPatient');
          $patient->setWithId(intval($v));
          $patient->setToStatus('enfant');
          $patient->setRelation();
        }
      }

      // fratrie
      if(!empty($d[12])) {
        foreach(explode(',', $d[12]) as $k=>$v) {
            $patient->setRelationType('relationPatientPatient');
            $patient->setWithId(intval($v));
            $patient->setToStatus('sœur / frère');
            $patient->setRelation();
        }
      }

}
echo 'ok';
