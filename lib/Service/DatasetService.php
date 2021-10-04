<?php
/**
 * Analytics
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <analytics@scherello.de>
 * @copyright 2021 Marcel Scherello
 */

namespace OCA\Analytics\Service;

use OCA\Analytics\Activity\ActivityManager;
use OCA\Analytics\Controller\DatasourceController;
use OCA\Analytics\Db\DataloadMapper;
use OCA\Analytics\Db\DatasetMapper;
use OCA\Analytics\Db\StorageMapper;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IRootFolder;
use OCP\ITagManager;
use Psr\Log\LoggerInterface;

class DatasetService
{
    private $userId;
    private $logger;
    private $tagManager;
    private $ShareService;
    private $StorageMapper;
    private $DatasetMapper;
    private $ThresholdService;
    private $DataloadMapper;
    private $ActivityManager;
    private $rootFolder;
    private $VariableService;

    public function __construct(
        $userId,
        LoggerInterface $logger,
        ITagManager $tagManager,
        ShareService $ShareService,
        StorageMapper $StorageMapper,
        DatasetMapper $DatasetMapper,
        ThresholdService $ThresholdService,
        DataloadMapper $DataloadMapper,
        ActivityManager $ActivityManager,
        IRootFolder $rootFolder,
        VariableService $VariableService
    )
    {
        $this->userId = $userId;
        $this->logger = $logger;
        $this->tagManager = $tagManager;
        $this->ShareService = $ShareService;
        $this->ThresholdService = $ThresholdService;
        $this->StorageMapper = $StorageMapper;
        $this->DatasetMapper = $DatasetMapper;
        $this->DataloadMapper = $DataloadMapper;
        $this->ActivityManager = $ActivityManager;
        $this->rootFolder = $rootFolder;
        $this->VariableService = $VariableService;
    }

    /**
     * get all datasets
     *
     * @return DataResponse
     */
    public function index()
    {
        $ownDatasets = $this->DatasetMapper->index();

        // get dataload indicators for icons shown in the advanced screen
        $dataloads = $this->DataloadMapper->getAllDataloadMetadata();
        foreach ($dataloads as $dataload) {
            $key = array_search($dataload['dataset'], array_column($ownDatasets, 'id'));
            if ($key !== '') {
                if ($dataload['schedules'] !== '' and $dataload['schedules'] !== null) {
                    $dataload['schedules'] = 1;
                } else {
                    $dataload['schedules'] = 0;
                }
                $ownDatasets[$key]['dataloads'] = $dataload['dataloads'];
                $ownDatasets[$key]['schedules'] = $dataload['schedules'];
            }
        }

        foreach ($ownDatasets as &$ownDataset) {
            $ownDataset = $this->VariableService->replaceTextVariables($ownDataset);
        }

        return new DataResponse($ownDatasets);
    }

    /**
     * get own dataset details
     *
     * @param int $datasetId
     * @return array
     */
    public function read(int $datasetId)
    {
        $ownDataset = $this->DatasetMapper->read($datasetId);
        if (!empty($ownDataset)) {
            $ownDataset['permissions'] = \OCP\Constants::PERMISSION_UPDATE;
        }
        return $ownDataset;
    }

    /**
     * get own dataset details
     *
     * @param int $datasetId
     * @param string|null $user_id
     * @return array
     */
    public function getOwnDataset(int $datasetId, string $user_id = null)
    {
        $ownDataset = $this->DatasetMapper->read($datasetId, $user_id);
        if (!empty($ownDataset)) {
            $ownDataset['permissions'] = \OCP\Constants::PERMISSION_UPDATE;
            $ownDataset = $this->VariableService->replaceTextVariables($ownDataset);
        }
        return $ownDataset;
    }

    /**
     * create new dataset
     *
     * @param $name
     * @param $dimension1
     * @param $dimension2
     * @param $value
     * @return int
     * @throws \OCP\DB\Exception
     */
    public function create($name, $dimension1, $dimension2, $value)
    {
        //$this->ActivityManager->triggerEvent(0, ActivityManager::OBJECT_DATASET, ActivityManager::SUBJECT_DATASET_ADD);
        return $this->DatasetMapper->create($name, $dimension1, $dimension2, $value);
    }

    /**
     * get dataset details
     *
     * @param int $datasetId
     * @param $name
     * @param $subheader
     * @param int $parent
     * @param int $type
     * @param $link
     * @param $visualization
     * @param $chart
     * @param $chartoptions
     * @param $dataoptions
     * @param $dimension1
     * @param $dimension2
     * @param $value
     * @return bool
     */
    public function update(int $datasetId, $name, $subheader, int $parent, int $type, $link, $visualization, $chart, $chartoptions, $dataoptions, $dimension1 = null, $dimension2 = null, $value = null)
    {
        if ($type === DatasourceController::DATASET_TYPE_GROUP) {
            $parent = 0;
        }
        return $this->DatasetMapper->update($datasetId, $name, $subheader, $parent, $type, $link, $visualization, $chart, $chartoptions, $dataoptions, $dimension1, $dimension2, $value);
    }

    /**
     * Import Dataset from File
     *
     * @param string|null $path
     * @param string|null $raw
     * @return int
     * @throws \OCP\Files\NotFoundException
     * @throws \OCP\Files\NotPermittedException
     */
    public function import(string $path = null, string $raw = null)
    {
        if ($path !== '') {
            $file = $this->rootFolder->getUserFolder($this->userId)->get($path);
            $data = $file->getContent();
        } else if ($raw !== null) {
            $data = $raw;
        } else {
            return false;
        }
        $data = json_decode($data, true);

        $dataset = $data['dataset'];
        isset($dataset['name']) ? $name = $dataset['name'] : $name = '';
        isset($dataset['subheader']) ? $subheader = $dataset['subheader'] : $subheader = '';
        $parent = 0;
        isset($dataset['type']) ? $type = $dataset['type'] : $type = null;
        isset($dataset['link']) ? $link = $dataset['link'] : $link = null;
        isset($dataset['visualization']) ? $visualization = $dataset['visualization'] : $visualization = null;
        isset($dataset['chart']) ? $chart = $dataset['chart'] : $chart = null;
        isset($dataset['chartoptions']) ? $chartoptions = $dataset['chartoptions'] : $chartoptions = null;
        isset($dataset['dataoptions']) ? $dataoptions = $dataset['dataoptions'] : $dataoptions = null;
        isset($dataset['filteroptions']) ? $filteroptions = $dataset['filteroptions'] : $filteroptions = null;
        isset($dataset['dimension1']) ? $dimension1 = $dataset['dimension1'] : $dimension1 = null;
        isset($dataset['dimension2']) ? $dimension2 = $dataset['dimension2'] : $dimension2 = null;
        isset($dataset['value']) ? $value = $dataset['value'] : $value = null;

        $datasetId = $this->DatasetMapper->create();
        $this->DatasetMapper->update($datasetId, $name, $subheader, $parent, $type, $link, $visualization, $chart, $chartoptions, $dataoptions, $dimension1, $dimension2, $value, $filteroptions);

        foreach ($data['dataload'] as $dataload) {
            isset($dataload['datasource']) ? $datasource = $dataload['datasource'] : $datasource = null;
            isset($dataload['name']) ? $name = $dataload['name'] : $name = null;
            isset($dataload['option']) ? $option = $dataload['option'] : $option = null;
            $schedule = null;

            $dataloadId = $this->DataloadMapper->create($datasetId, $datasource);
            $this->DataloadMapper->update($dataloadId, $name, $option, $schedule);
        }

        foreach ($data['threshold'] as $threshold) {
            isset($threshold['dimension1']) ? $dimension1 = $threshold['dimension1'] : $dimension1 = null;
            isset($threshold['value']) ? $value = $threshold['value'] : $value = null;
            isset($threshold['option']) ? $option = $threshold['option'] : $option = null;
            isset($threshold['severity']) ? $severity = $threshold['severity'] : $severity = null;
            $this->ThresholdService->create($datasetId, $dimension1, $option, $value, $severity);
        }

        foreach ($data['data'] as $dData) {
            isset($dData[0]) ? $dimension1 = $dData[0] : $dimension1 = null;
            isset($dData[1]) ? $dimension2 = $dData[1] : $dimension2 = null;
            isset($dData[2]) ? $value = $dData[2] : $value = null;
            $this->StorageMapper->create($datasetId, $dimension1, $dimension2, $value);
        }

        if (isset($data['favorite'])) {
            $this->setFavorite($datasetId, $data['favorite']);
        }

        return $datasetId;
    }

    /**
     * Export Dataset
     *
     * @param int $datasetId
     * @return DataDownloadResponse
     */
    public function export(int $datasetId)
    {
        $result = array();
        $result['dataset'] = $this->DatasetMapper->read($datasetId);
        $result['dataload'] = $this->DataloadMapper->read($datasetId);
        $result['threshold'] = $this->ThresholdService->read($datasetId);
        $result['favorite'] = '';

        if ($result['dataset']['type'] === DatasourceController::DATASET_TYPE_INTERNAL_DB) {
            $result['data'] = $this->StorageMapper->read($datasetId);
        }

        unset($result['dataset']['id'], $result['dataset']['user_id'], $result['dataset']['user_id'], $result['dataset']['parent']);
        $data = json_encode($result);
        return new DataDownloadResponse($data, $result['dataset']['name'] . '.export.txt', 'text/plain; charset=utf-8');
    }

    /**
     * Delete Dataset and all depending objects
     *
     * @param int $datasetId
     * @return bool
     */
    public function delete(int $datasetId)
    {
        $this->ShareService->deleteShareByReport($datasetId);
        $this->StorageMapper->deleteByDataset($datasetId);
        $this->DatasetMapper->delete($datasetId);
        $this->ThresholdService->deleteThresholdByReport($datasetId);
        $this->DataloadMapper->deleteDataloadByDataset($datasetId);
        $this->ActivityManager->triggerEvent(0, ActivityManager::OBJECT_DATASET, ActivityManager::SUBJECT_DATASET_DELETE);
        $this->setFavorite($datasetId, 'false');
        return true;
    }

    /**
     * get dataset details
     *
     * @param int $datasetId
     * @param $chartoptions
     * @param $dataoptions
     * @param $filteroptions
     * @return bool
     */
    public function updateOptions(int $datasetId, $chartoptions, $dataoptions, $filteroptions)
    {
        return $this->DatasetMapper->updateOptions($datasetId, $chartoptions, $dataoptions, $filteroptions);
    }
}