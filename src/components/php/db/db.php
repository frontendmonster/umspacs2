
<?php
static $char_set;
function connect($dbname)
{
    global $char_set;
    $servername = 'localhost';
    $username = 'root';
    // $password = 'A1l2i3 !@#';
    $password = '';

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        echo 'Error: '.$e->getMessage();
        return;
    }
}
function getReportInfo($study_id)
{
    $conn  = connect('dicom');
    $query = "SELECT * FROM `reports` WHERE study_fk=:study_pk";
    $query = $conn->prepare($query);
    $query->bindParam(':study_pk', $study_id);
    $query->execute();
    $result = $query->fetchAll();
    if (isset($result[0])) {
        return $result[0];
    }
    return "Report Not Existed";
}

function getStudyInfoForReport($studyId='')
{
    $conn = connect('pacsdb');
    if ($conn == null) {
        return 404;
    }
    $query = 'SELECT
              patient.pat_id,
              patient.pat_name,
              patient.pat_birthdate,
              patient.pat_sex,
              patient.pat_id,
              study.pk AS study_pk,
              study.mods_in_study,
              study.accession_no,
              study.study_desc,
              study.study_datetime
            from study
            LEFT JOIN patient ON study.patient_fk = patient.pk
            WHERE study.study_id = :studyId;';
    $query = $conn->prepare($query);
    $query->bindParam(':studyId', $studyId);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);

    $studyPk = $result['study_pk'];
    $query = "SELECT DISTINCT body_part
            FROM series
            LEFT JOIN study ON series.study_fk = study.pk
            WHERE study.pk = :studyPk;";
    $query = $conn->prepare($query);
    $query->bindParam(':studyPk', $studyPk);
    $query->execute();
    $bodyParts = $query->fetchAll(PDO::FETCH_COLUMN, 0);
    $result['body_parts'] = $bodyParts;
    return $result;
}
function getAllPatient()
{
    $conn = connect('pacsdb');
    $query = 'SELECT
                patient.pk,
                patient.pat_id ,
                patient.pat_name,
                patient.pat_sex,
                study.num_series,
                study.pk AS study_pk,
                study.mods_in_study,
                study.num_instances,
                study.study_iuid,
                patient.pat_birthdate,
                study.study_id,
                study.study_datetime,
                study.study_desc,
                study.study_status
              FROM patient
              INNER JOIN study
              ON patient.pk = study.patient_fk
              ORDER BY study.study_datetime DESC;';
    $query = $conn->prepare($query);
    $query->execute();
    $result = $query->fetchAll();

    return $result;
}

function searchByStudyId($studyId)
{
    $conn = connect('pacsdb');
    $query = 'SELECT
                patient.pk AS pat_pk,
                patient.pat_id,
                patient.pat_name,
                patient.pat_sex,
                study.pk AS study_pk,
                study.study_id,
                study.study_datetime,
                study.study_desc,
                study.study_status
              FROM patient
              INNER JOIN study ON patient.pk = study.patient_fk
              WHERE study.study_id = :study_id;';
    $query = $conn->prepare($query);
    $query->bindParam(':study_id', $studyId);
    $query->execute();

    $result = $query->fetchAll();

    return $result;
}

function getStudyId($studyPk)
{
    $conn = connect('pacsdb');
    $query = $conn->prepare('SELECT study_iuid FROM study WHERE pk = :studyPk;');
    $query->bindParam(':studyPk', $studyPk);
    $query->execute();

    $result = $query->fetch(PDO::FETCH_ASSOC);

    return $result;
}

function getAllSeries($study_pk)
{
    $conn = connect('pacsdb');
    $query = 'SELECT
                series.pk,
                series.modality,
                series.body_part,
                series.num_instances,
                series.series_no,
                series.series_desc,
                series.study_fk,
                series.series_iuid
             FROM series
             WHERE series.study_fk = :study_pk
             ORDER BY series.series_no;';
    $query = $conn->prepare($query);
    $query->bindParam(':study_pk', $study_pk);
    $query->execute();
    $result = $query->fetchAll();

    return $result;
}

function getAllInstances($serie_pk)
{
    $conn = connect('pacsdb');
    $query = $conn->prepare('SELECT instance.sop_iuid, instance.sop_cuid
                           FROM instance WHERE series_fk = :serie_pk ORDER BY instance.sop_iuid DESC;');
    $query->bindParam(':serie_pk', $serie_pk);
    $query->execute();
    $result = $query->fetchAll();

    return $result;
}

function searchStudies($patient_id = null, $name = null, $accession = null, $modality = null, $from = null, $to = null, $institution = null, $page_index = 1, $page_size = 20)
{
    global $char_set;
    $inQuery = null;
    $modalities = null;

    $start_index = $page_index * $page_size;
    $start_index = (int)$start_index;
    $page_size = (int)$page_size;

    if (isset($modality)) {
        $modality = strtolower($modality);
        $modalities = explode('\\\\', $modality);
        $inQuery = implode(',', array_fill(0, count($modalities), '?'));
    }
    $conn = connect('pacsdb');

    $querySelect = 'SELECT SQL_CALC_FOUND_ROWS
                patient.pk,
                patient.pat_id,
                patient.pat_name,
                patient.pat_sex,
                patient.pat_birthdate,
                study.num_series,
                study.pk AS study_pk,
                study.mods_in_study,
                study.num_instances,
                study.study_iuid,
                study.study_id,
                study.study_datetime,
                study.study_desc,
                study.study_status,
                study.accession_no,
                series.institution
              FROM study
              LEFT JOIN patient ON patient.pk = study.patient_fk
              LEFT JOIN series ON series.study_fk = study.pk';

    $queryBase = ' WHERE 1 = 1';

    if (isset($patient_id)) {
        $patient_id = strtolower($patient_id);
        $queryBase .= ' AND LOWER(patient.pat_id) LIKE CONCAT (?,"%")';
    }
    if (isset($name)) {
        $name = strtolower($name);
        $queryBase .= ' AND LOWER(patient.pat_name) LIKE CONCAT ("%",?,"%")';
    }
    if (isset($accession)) {
        $accession = strtolower($accession);
        $queryBase .= ' AND study.accession_no LIKE CONCAT (?,"%")';
    }
    if (isset($inQuery)) {
        $queryBase .= ' AND LOWER(LEFT(study.mods_in_study,2)) IN(' . $inQuery . ')';
    }
    if (isset($from)) {
        $queryBase .= ' AND study.study_datetime >= ?';
    }
    if (isset($to)) {
        $queryBase .= ' AND study.study_datetime <= ?';
    }
    if (isset($institution)) {
        $queryBase .= ' AND LOWER(series.institution) LIKE CONCAT ("%",?,"%")';
    }

    $queryGroup = ' GROUP BY
              patient.pk,
              patient.pat_id,
              patient.pat_name,
              patient.pat_sex,
              patient.pat_birthdate,
              study.num_series,
              study_pk,
              study.mods_in_study,
              study.num_instances,
              study.study_iuid,
              study.study_id,
              study.study_datetime,
              study.study_desc,
              study.accession_no,
              study.study_status';

    $queryOrder = " ORDER BY study.study_datetime DESC ";
    $queryLimit = "LIMIT $page_size OFFSET $start_index ";
    $data_query = $querySelect . $queryBase . $queryGroup . $queryOrder . $queryLimit;

    $data_query = $conn->prepare($data_query);
    $i = 1;

    if (isset($patient_id)) {
        $data_query->bindValue($i, $patient_id);
        $i++;
    }

    if (isset($name)) {
        $data_query->bindValue($i, $name);
        $i++;
    }

    if (isset($accession)) {
        $data_query->bindValue($i, $accession);
        $i++;
    }

    if (isset($modalities)) {
        foreach ($modalities as $k => $modality) {
            $data_query->bindValue($i, $modality);
            $i++;
        }
    }

    if (isset($from)) {
        $data_query->bindValue($i, $from);
        $i++;
    }

    if (isset($to)) {
        $data_query->bindValue($i, $to);
        $i++;
    }

    if (isset($institution)) {
        $institution = strtolower($institution);
        $data_query->bindValue($i, $institution);
        $i++;
    }

    // var_dump($data_query);
    $data_query->execute();
    $result = $data_query->fetchAll();

    // var_dump($result);

    $query = 'SELECT FOUND_ROWS();';
    $query = $conn->prepare($query);
    $query->execute();
    $count = $query->fetch(PDO::FETCH_COLUMN);
    // $studyCount = sizeof($result);
    $serieCount = getSerieCount($result);
    $instanceCount = getInstanceCount($result);
    // $result = array_slice($result, $start_index , $page_size);
    $result['studyCount'] = $count;
    $result['serieCount'] = $serieCount;
    $result['instanceCount'] = $instanceCount;
    return $result;
}

function getSerieCount($data)
{
    $result = 0;
    foreach ($data as $key => $value) {
        $result += $value['num_series'];
    }
    return $result;
}

function getInstanceCount($data)
{
    $result = 0;
    foreach ($data as $key => $value) {
        $result += $value['num_instances'];
    }

    return $result;
}

function getInstitutionName($study_pk)
{
    $conn = connect('pacsdb');
    $query = "SELECT series.institution FROM series WHERE series.study_fk = :study_pk ;";
    $query = $conn->prepare($query);

    $query->bindParam(':study_pk', $study_pk);

    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);
    return $result;
}
function getAllModalities($dynamic=false)
{
    if ($dynamic) {
        updateAllModalities();
    }
    $conn = connect('dicom');
    $query = "SELECT * FROM modalities ORDER BY modality";
    $query = $conn->prepare($query);

    $query->execute();
    $result = $query->fetchAll();
    return $result;
}

function getAllInstitutions($dynamic=false)
{
    if ($dynamic) {
        updateInstituations();
    }
    $conn = connect('dicom');
    $query = "SELECT * FROM institutions ORDER BY name";
    $query = $conn->prepare($query);

    $query->execute();
    $result = $query->fetchAll();
    return $result;
}

function updateInstituations()
{
    // Get All Dynamic Insts
    $conn = connect('pacsdb');
    $query = "SELECT DISTINCT series.institution FROM `series`;";
    $query = $conn->prepare($query);

    $query->execute();
    $allDynamicInsts = $query->fetchAll(PDO::FETCH_COLUMN, 0);

    // Get All Static Inst
    $allStaticInsts = getAllStaticInsts();
    $conn = connect('dicom');

    foreach ($allStaticInsts as $key => $instName) {
        if (!in_array($instName, $allDynamicInsts)) {
            removeStaticInst($instName);
        }
    }
    // Check if exixts
    foreach ($allDynamicInsts as $key => $instName) {
        if (in_array($instName, $allStaticInsts)) {
            continue;
        }

        $query = "INSERT INTO `institutions` (`name`) VALUES ('$instName');";
        $query = $conn->prepare($query);
        $query->execute();
    }
}

function removeStaticInst($instName)
{
    $conn = connect('dicom');
    $query = "DELETE FROM `institutions` WHERE `name`=:name";
    $query = $conn->prepare($query);
    $query->bindParam(':name', $instName);
    $query->execute();
}

function getAllStaticInsts()
{
    $conn = connect('dicom');
    $query = "SELECT * from `institutions`";
    $query = $conn->prepare($query);
    $query->execute();
    $result = $query->fetchAll(PDO::FETCH_COLUMN, 1);

    return $result;
}


function updateAllModalities()
{
    // Get All Dynamic Modalities
    $conn = connect('pacsdb');
    $query = "SELECT DISTINCT study.mods_in_study FROM `study`;";
    $query = $conn->prepare($query);

    $query->execute();
    $allDynamicMods = $query->fetchAll(PDO::FETCH_COLUMN, 0);

    $allMods = [];
    foreach ($allDynamicMods as $key => $value) {
        $values = explode('\\', $value);
        foreach ($values as $key2 => $value2) {
            if (!in_array($value2, $allMods)) {
                $allMods[] = $value2;
            }
        }
    }
    $allDynamicMods = $allMods;

    // Get All Static Inst
    $allStaticMods = getAllStaticMods();
    $conn = connect('dicom');

    foreach ($allStaticMods as $key => $modName) {
        if (!in_array($modName, $allDynamicMods)) {
            removeStaticMod($modName);
        }
    }

    foreach ($allDynamicMods as $key => $modName) {
        if (in_array($modName, $allStaticMods)) {
            continue;
        }
        $query = "INSERT INTO `modalities` (`modality`) VALUES ('$modName');";

        $query = $conn->prepare($query);
        $query->execute();
    }
}

function removeStaticMod($modName)
{
    $conn = connect('dicom');
    $query = "DELETE FROM `modalities` WHERE `modality`=:modality";
    $query = $conn->prepare($query);
    $query->bindParam(':modality', $modName);
    $query->execute();
}

function getAllStaticMods()
{
    $conn = connect('dicom');
    $query = "SELECT * from `modalities`";
    $query = $conn->prepare($query);
    $query->execute();
    $result = $query->fetchAll(PDO::FETCH_COLUMN, 1);

    return $result;
}

function getAllUsers()
{
    $conn = connect('dicom');
    $query = "SELECT username, role, email FROM users";
    $query = $conn->prepare($query);

    $query->execute();
    $result = $query->fetchAll();
    return $result;
}

function setModalityVisibility($visibility, $id)
{
    if (!isset($visibility) || !isset($id)) {
        return false;
    }
    $visibility = ($visibility == 'true') ? 1 : 0;
    $conn = connect('dicom');
    $query = "UPDATE `modalities` SET `visibility`= :visibility WHERE `id` = :id; ";
    $query =$conn->prepare($query);
    $query->bindParam(':visibility', $visibility);
    $query->bindParam(':id', $id);
    $query->execute();

    return true;
}

function setInstitutionVisibility($visibility, $id)
{
    if (!isset($visibility) || !isset($id)) {
        return false;
    }
    $visibility = ($visibility == 'true') ? 1 : 0;
    $conn = connect('dicom');
    $query = "UPDATE `institutions` SET `visibility`=:visibility WHERE `id` = :id;";
    $query =$conn->prepare($query);
    $query->bindParam(':visibility', $visibility);
    $query->bindParam(':id', $id);
    $query->execute();

    return true;
}

function getAllIgnoredModalities()
{
    $conn  = connect('dicom');
    $query = "SELECT * FROM `modalities` WHERE `visibility` = 0;";
    $query = $conn->prepare($query);
    $query->execute();
    $result = $query->fetchAll();

    return $result;
}

function getAllIgnoredInstitution()
{
    $conn = connect('dicom');
    $query = "SELECT * FROM `institutions` WHERE `visibility` = 0;";
    $query =$conn->prepare($query);
    $query->execute();
    $result = $query->fetchAll();

    return $result;
}

function submitReport($study_pk, $patient_name, $findings, $impression, $comments, $doctor_name, $report_date)
{
    $conn  = connect('dicom');

    if (isReportExistFor($study_pk)) {
        $query = "UPDATE `reports` SET `farsi_name`=:name,`findings`=:findings,`impression`=:impression,`comments`=:comments,`dr_name`=:doctor_name,`report_date`=:report_date WHERE `study_fk`=:study_fk;";
        $query = $conn->prepare($query);
        $query->bindParam(':name', $patient_name);
        $query->bindParam(':findings', $findings);
        $query->bindParam(':impression', $impression);
        $query->bindParam(':comments', $comments);
        $query->bindParam(':doctor_name', $doctor_name);
        $query->bindParam(':report_date', $report_date);
        $query->bindParam(':study_fk', $study_pk);
        $query->execute();
        echo "Updated";
    } else {
        $query = "INSERT INTO `reports`(`study_fk`, `farsi_name`, `findings`, `impression`, `comments`, `dr_name`, `report_date`) VALUES (:study_fk, :patient_name ,:findings ,:impression ,:comments ,:doctor_name ,:report_date)";
        $query = $conn->prepare($query);
        $query->bindParam(':study_fk', $study_pk);
        $query->bindParam(':patient_name', $patient_name);
        $query->bindParam(':findings', $findings);
        $query->bindParam(':impression', $impression);
        $query->bindParam(':comments', $comments);
        $query->bindParam(':doctor_name', $doctor_name);
        $query->bindParam(':report_date', $report_date);
        $query->execute();
        echo "Created";
    }
    return true;
}

function isReportExistFor($study_pk)
{
    $conn  = connect('dicom');
    $query = "SELECT count(*) FROM `reports` WHERE `study_fk`=:study_fk";
    $query = $conn->prepare($query);
    $query->bindParam(':study_fk', $study_pk);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_COLUMN);

    return $result;
}
?>
