<?php

namespace Viessmann\API;

use TomPHP\Siren\Entity;
use Viessmann\Oauth\ViessmannOauthClient;

final class ViessmannAPI
{
    const BOILER_TEMP="heating.boiler.sensors.temperature.main";
    const HEATING_BURNER="heating.burner";
    const HEATING_CIRCUITS="heating.circuits";
    const HEATING_CURVE="heating.curve";
    const ACTIVE_OPERATING_MODE="operating.modes.active";
    const HEATING_OPERATING_MODES="operating.modes.active";
    const HEATING_DWH_MODE="operating.modes.dhw";
    const HEATING_DWH_AND_HEATING_MODE="operating.modes.dhwAndHeating";
    const HEATING_FORCED_NORMAL_MODE="operating.modes.forcedNormal";
    const HEATING_FORCED_REDUCTED_MODE="operating.modes.forcedReduced";
    const HEATING_STANDY_MODE="operating.modes.standby";
    const HEATING_PROGRAM_ACTIVE="operating.programs.active";
    const HEATING_PROGRAM_COMFORT="operating.programs.comfort";
    const HEATING_PROGRAM_ECO="operating.programs.eco";
    const HEATING_PROGRAM_EXTERNAL="operating.programs.external";
    const HEATING_PROGRAM_NORMAL="operating.programs.normal";
    const HEATING_PROGRAM_REDUCED="operating.programs.reduced";
    const HEATING_PROGRAM_STANDBY="operating.programs.standby";
    const HEATING_PROGRAM_SUPPLY="sensors.temperature.supply";
    const HEATING_TIME_OFFSET="heating.device.time.offset";
    const HEATING_DWH="heating.dhw";
    const HEATING_CIRCUITS_1_DHW="heating.circuits.1.dhw";
    const HEATING_DWH_TEMPERATURE="heating.dhw.temperature";
    const HEATING_DWH_SENSORS="heating.dhw.sensors";
    const HEATING_DWH_SENSORS_TEMPERATURE="heating.dhw.sensors.temperature";
    const HEATING_DWH_SCHEDULE="heating.dhw.schedule";
    const HEATING_DWH_TEMPERATURE_HOTWATER_STORAGE="heating.dhw.sensors.temperature.hotWaterStorage";
    const HEATING_DWH_TEMPERATURE_OUTLET="heating.dhw.sensors.temperature.outlet";
    const HEATING_GAS_CONSUMPTION_DHW="heating.gas.consumption.dhw";
    const HEATING_TEMP_OUTSIDE="heating.sensors.temperature.outside";
    const HEATING_SENSORS_TEMPERATURE="heating.sensors.temperature";
    const HEATING_CIRCUITS_0_CIRCULATION_SCHEDULE="circulation.schedule";
    const HEATING_CIRCUITS_0_HEATING_SCHEDULE="heating.schedule";
    private $installationId;
    private $gatewayId;
    private $viessmanAuthClient;
    private $featureHeatingUrl;
    private $circuitId;

    /**
     * ViessmannAPI constructor.
     */
    public function __construct($params)
    {
        $this->circuitId=$param["circuitId"]??0;
        $this->viessmanAuthClient=new ViessmannOauthClient($params);
        $code=$this->viessmanAuthClient->getCode();
        $this->viessmanAuthClient->getToken($code);
        $installationJson=$this->viessmanAuthClient->readData("general-management/installations");
        $installationEntity=Entity::fromArray(json_decode($installationJson,true));
        $modelInstallationEntity=$installationEntity->getEntities()[0];
        $this->installationId=$modelInstallationEntity->getProperty('id');
        $modelDevice=$modelInstallationEntity->getEntities()[0];
        $this->gatewayId=$modelDevice->getProperty('serial');
        $this->featureHeatingUrl="operational-data/installations/".$this->installationId."/gateways/".$this->gatewayId."/devices/".($params["deviceId"]?? 0)."/features";

    }


    public function getInstallationId():string{
        return $this->installationId."";
    }

    /**
     * @return string
     */
    public function getGatewayId(): string
    {
        return $this->gatewayId."";
    }
    public function getFeatures():String{
        return $this->viessmanAuthClient->readData($this->featureHeatingUrl);
    }

    public function getOutsideTemperature():string{
        $outsideTempEntity = $this->getEntity(ViessmannFeature::HEATING_SENSORS_TEMPERATURE_OUTSIDE);
        return $outsideTempEntity->getProperty("value")["value"]."";
    }
    public function getBoilerTemperature():string{
        $boilerTempEntity = $this->getEntity(ViessmannFeature::HEATING_BOILER_SENSORS_TEMPERATURE_MAIN);
        return $boilerTempEntity->getProperty("value")["value"]."";
    }
    public function getSlope($circuitId=NULL):string{
        $curveEntity=$this->getEntity($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_CURVE));
        return $curveEntity->getProperty("slope")["value"]."";
    }

    public function getShift($circuitId=NULL):string{
        $curveEntity=$this->getEntity($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_CURVE));
        return $curveEntity->getProperty("shift")["value"]."";
    }
    public function setCurve($shift,$slope,$circuitId=NULL){
        $this->setRawJsonData($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_CURVE),"setCurve","{\"shift\":".$shift.",\"slope\":".$slope."}");
    }
    public function getActiveMode($circuitId=NULL):string{
        $activeModeEntity=$this->getEntity($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::ACTIVE_OPERATING_MODE));
        return $activeModeEntity->getProperty("value")["value"]."";

    }
    public function setActiveMode($mode,$circuitId=NULL){
        $this->setRawJsonData($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_OPERATING_MODES),"setMode","{\"mode\":\"".$mode."\"}");
    }
    public function getActiveProgram($circuitId=NULL):string{
        $activeProgramEntity=$this->getEntity($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_PROGRAM_ACTIVE));
        return $activeProgramEntity->getProperty("value")["value"]."";
    }

    public function isHeatingBurnerActive(): bool {
        $heatingBurnerEntity = $this->getEntity(ViessmannFeature::HEATING_BURNER);
        return $heatingBurnerEntity->getProperty("active")["value"];
    }

    public function isDhwModeActive($circuitId=NULL):bool{
        $dhwModeActiveEntity=$this->getEntity($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_DWH_MODE));
        return $dhwModeActiveEntity->getProperty("active")["value"];
    }
    public function getComfortProgramTemperature($circuitId=NULL):string{
        $comfortProgramEntity=$this->getEntity($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_PROGRAM_COMFORT));
        return $comfortProgramEntity->getProperty("temperature")["value"]."";
    }
    public function setComfortProgramTemperature($temperature,$circuitId=NULL){
        $this->setRawJsonData($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_PROGRAM_COMFORT),"setTemperature","{\"targetTemperature\":".$temperature."}");
    }
    public function getEcoProgramTemperature($circuitId=NULL):string{
        $ecoProgramEntity=$this->getEntity($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_PROGRAM_ECO));
        return $ecoProgramEntity->getProperty("temperature")["value"]."";
    }
    public function activateEcoProgram($temperature,$circuitId=NULL){
        $this->setRawJsonData($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_PROGRAM_ECO),"activate","{\"temperature\":".$temperature."}");
    }
    public function deActivateEcoProgram($circuitId=NULL){
        $this->setRawJsonData($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_PROGRAM_ECO),"deactivate",null);
    }
    public function getExternalProgramTemperature($circuitId=NULL):string{
        $externalProgramEntity=$this->getEntity($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_PROGRAM_EXTERNAL));
        return $externalProgramEntity->getProperty("temperature")["value"]."";
    }
    public function setExternalProgramTemperature($temperature,$circuitId=NULL){
        $this->setRawJsonData(ViessmannFeature::HEATING_PROGRAM_REDUCED, "setTemperature", "{\"targetTemperature\":" . $temperature . "}");
    }
    public function getNormalProgramTemperature($circuitId=NULL):string{
        $normalProgramEntity=$this->getEntity($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_PROGRAM_NORMAL));
        return $normalProgramEntity->getProperty("temperature")["value"]."";
    }
    public function setNormalProgramTemperature($temperature,$circuitId=NULL){
        $this->setRawJsonData($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_PROGRAM_NORMAL),"setTemperature","{\"targetTemperature\":".$temperature."}");
    }
    public function getReducedProgramTemperature($circuitId=NULL):string{
        $reducedProgramEntity=$this->getEntity($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_PROGRAM_REDUCED));
        return $reducedProgramEntity->getProperty("temperature")["value"]."";
    }
    public function setReducedProgramTemperature($temperature,$circuitId=NULL){
        $this->setRawJsonData($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_PROGRAM_REDUCED),"setTemperature","{\"targetTemperature\":".$temperature."}");
    }
    public function isInStandbyMode($circuitId=NULL):bool{
        $standbyProgramEntity=$this->getEntity($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_PROGRAM_STANDBY));
        return $standbyProgramEntity->getProperty("active")["value"];
    }
    public function getSupplyProgramTemperature($circuitId=NULL):string{
        $supplyProgramEntity=$this->getEntity($this->buildFeature(self::HEATING_CIRCUITS,$circuitId,self::HEATING_PROGRAM_SUPPLY));
        return $supplyProgramEntity->getProperty("value")["value"]."";
    }
    public function getRawJsonData($resources):string{
        try{
            return $this->viessmanAuthClient->readData($this->featureHeatingUrl."/".$resources);
        }catch (TokenResponseException $e){
            throw new \ViessmannApiException("Erreur lors de l'appel. Si 400 bad Request alors mauvais structure de données. Si 502, souvent une erreur de format donnée(20.0 au lieu de 20,...)",0,$e);
        }
    }
    private function getEntity($resources):Entity{
        return Entity::fromArray(json_decode($this->getRawJsonData($resources),true));
    }
    public function setRawJsonData($feature, $action, $data){
        $this->viessmanAuthClient->setData($this->featureHeatingUrl."/".$feature."/".$action,$data);
    }
    public function setDhwTemperature($temperature){
        $data="{\"temperature\": $temperature}";
        $this->viessmanAuthClient->setRawJsonData(ViessmannFeature::HEATING_DHW_TEMPERATURE, "setTargetTemperature", $data);
    }
    private function buildFeature($prefix,$circuitId,$feature){
        if ($circuitId==NULL){
            $circuitId=$this->circuitId;
        }
        return $prefix.".".$circuitId.".".$feature;
    }

}
