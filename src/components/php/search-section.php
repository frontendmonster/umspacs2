<section id="search-section" class="row">
  <form id="search-from" class="col-xs-12" role="form">
    <input id="searchById" type="search" class="form-control search-control" placeholder="Id">
    <input id="searchByName" type="search" class="form-control search-control" placeholder="Name">
    <!-- <input id="searchByModality" type="search" class="form-control search-control hidden-xs" placeholder="Modality"> -->
    <select title="Modality" id="searchByModality" class="form-control search-control hidden-xs selectpicker" multiple >
      <option>DX</option>
      <option>CT</option>
      <option>MR</option>
      <option>SR</option>
      <option>OT</option>
      <option>KO</option>
    </select>
    <select title="Institution" id="searchByInstitution" class="form-control search-control hidden-xs selectpicker" data-live-search="true">
      <option>Gonbad Payambar Azam</option>
      <option>Gonbad Motahari Hospital</option>
      <option>Al Jalil Agh Ghala</option>
      <option>Torkaman Imam Khomeyni Hospital</option>
      <option>Gorgan Taleghani Hospital</option>
      <option>5th Azar Hospital</option>
    </select>
    <!-- <input   placeholder="Modality"> -->
    <!-- <input id="searchByInstitution" type="search" class="form-control search-control hidden-xs" placeholder="Institution"> -->
    <input id="searchByFrom" type="text" class="form-control search-control hidden-xs" placeholder="From">
    <input id="searchByTo" type="text" class="form-control search-control hidden-xs" placeholder="To">
  </form>
</section>
