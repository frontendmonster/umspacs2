var $submitReport = $('#submit-report');
var $patientName = $('#patient-name');
var $patientId = $('#patient-id');
var $patientDOB = $('#patient-dob');
var $patientAge = $('#patient-age');
var $patientSex = $('#patient-sex');
var $studyMod = $('#study-mod');
var $studyPart = $('#study-part');
var $studyDate = $('#study-datetime');
var $studyDesc = $('#study-desc');
var $reportDate = $('#report-date');
var $doctorName = $('#doctor-name');

var $findings = $('#findings');
var $impression = $('#impression');
var $comments = $('#comments');

var invalidInputs = [];

$patientName.on('input', validateInputs);
$reportDate.on('input', validateInputs);
$doctorName.on('input', validateInputs);
$submitReport.on('click', handelSubmitReport);

function initForm() {
  var dob = convertStringDateToDate($patientDOB.val());
  $patientDOB.val(to_persian_date(dob));

  var name = fix_name($patientName.val());
  $patientName.val(name);

  var studyTime = convertStringDateToDate($studyDate.val());
  var age = getAge(studyTime, dob);
  $patientAge.val(age.age + ' ' + age.type);

  var sex = getSex($patientSex.val());
  $patientSex.val(sex);

  var mod = $studyMod.val().substr(0, 2);
  $studyMod.val(mod);

  var part = $studyPart.val().split('\\').filter(function(data) {
    if (data === '')
      return false;
    return true;
  }).join('/');
  $studyPart.val(part);

  $studyDate.val(to_persian_date(studyTime));

  var reportDate = $reportDate.val();
  var persianToday = to_persian_date(formatDate(new Date()));
  if (reportDate === '') {
    $reportDate.val(persianToday);
  } else {
    $reportDate.val(formatDate(reportDate));
  }

  var studyDesc = fix_name($studyDesc.val()).toUpperCase();
  $studyDesc.val(studyDesc);
}

function validateInputs() {
  function setInvalid(el) {
    el.addClass('invalid');
  }

  function setValid(el) {
    el.removeClass('invalid');
  }
  var name = $('#patient-name').val();
  var reportDate = $reportDate.val();
  var doctorName = $doctorName.val();
  // console.log(name);

  if (name === '' || !isPersian(name)) {
    setInvalid($patientName.parent());
    if (invalidInputs.indexOf('name') < 0)
      invalidInputs.push('name');
  } else {
    setValid($patientName.parent());
    invalidInputs = removeFromArray(invalidInputs, 'name');
  }

  if (reportDate === '' || !isValidDate(reportDate)) {
    setInvalid($reportDate.parent());
    if (invalidInputs.indexOf('reportDate') < 0)
      invalidInputs.push('reportDate');
  } else {
    setValid($reportDate.parent());
    invalidInputs = removeFromArray(invalidInputs, 'reportDate');
  }

  if (doctorName === '') {
    setInvalid($doctorName.parent());
    if (invalidInputs.indexOf('DrName') < 0)
      invalidInputs.push('DrName');
  } else {
    setValid($doctorName.parent());
    invalidInputs = removeFromArray(invalidInputs, 'DrName');
  }

  if (invalidInputs.length > 0) {
    $submitReport.addClass('disabled');
    $submitReport.attr('disabled');
  } else {
    $submitReport.removeClass('disabled');
    $submitReport.removeAttr('disabled');
  }

}

function handelSubmitReport() {
  if (canSubmitReport()) {
    console.log('Not Valid');
    return;
  }
  submitReport();
}

function canSubmitReport() {
  return invalidInputs.length > 0 || $submitReport.hasClass('disabled') || $submitReport.attr('disabled');
}

function submitReport() {
  var studyPk = $patientId.attr('data-studyPk');
  var name = $patientName.val();
  var findings = $findings.val();
  var impression = $impression.val();
  var comments = $comments.val();
  var doctorName = $doctorName.val();
  var reportDate = $reportDate.val();

  var url = 'src/components/php/api/service.php?action=submitreport' +
    '&study_pk=' + studyPk +
    '&patient_name=' + name +
    '&findings=' + findings +
    '&impression=' + impression +
    '&comments=' + comments +
    '&doctor_name=' + doctorName +
    '&report_date=' + reportDate;

    console.log(url);

    $.getJSON(url, function (data) {
      console.log(data);
    });
}
