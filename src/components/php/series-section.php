<section id="result-section" class="row" style="display:none;">
  <div class="col-xs-12 section-header">
    <h4>Series</h4>
    <button id="close-button" type="button" name="button" onclick="hideResultSection();">×</button>
  </div>
  <div class="section-content">
    <div id="serie-table-container" class="col-xs-12 col-sm-6 full-height">
      <div class="row full-height">
        <table id="series-table" class="table table-striped table-hover">
          <thead>
            <tr>
              <th>Body Part</th>
              <th>Images</th>
              <th>Description</th>
              <th class="hidden-xs">Viewer</th>
              <th class="visible-xs">Show</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
    <div id="viewer-mini" class="full-height col-xs-12 col-sm-6">
      <div id="thumb-div" class="row full-height"></div>
    </div>
  </div>



  <div id="viewer-modal" class="modal fade" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Modal Header</h4>
        </div>
        <div class="modal-body">
        </div>
        <div class="modal-actions">
          <button class="btn btn-primary" type="button" name="prev" onclick="prevFrame();">Prev</button>
          <button class="btn btn-primary" type="button" name="play" onclick="togglePause();">Play</button>
          <button class="btn btn-primary" type="button" name="next" onclick="nextFrame();">Next</button>
        </div>
      </div>
    </div>
  </div>
</section>
