<?php
use App\Core\Router;
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/applicants') ?>">&larr; Back to Applicant Directory</a>
</div>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Application for Rental Accommodation</h2>
        <p class="text-muted text-xs" style="margin-top: 4px;">The Landlord acknowledges the confidentiality of this document</p>
    </div>
    <div class="card-body">
        <!-- PDF Application Import & OCR / Handwriting Extractor -->
        <div id="pdf-import-dropzone" data-parse-url="<?= Router::url('/applicants/parse-pdf') ?>" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.04) 0%, rgba(16, 185, 129, 0.04) 100%); border: 2px dashed var(--ws-border); border-radius: 12px; padding: 16px 18px; margin-bottom: 24px; transition: border-color 0.2s ease, background 0.2s ease; cursor: pointer;">
            <input type="file" id="pdf-import-file-input" accept=".pdf,application/pdf" style="display: none;">

            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(37, 99, 235, 0.1); color: var(--ws-accent, #2563eb); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <div>
                        <div style="font-size: 14.5px; font-weight: 700; color: var(--ws-text-primary); display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span>Import Paper / Scanned PDF Application</span>
                            <span style="font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: rgba(37, 99, 235, 0.12); color: var(--ws-accent, #2563eb);">Handwriting &bull; Print &bull; Typed OCR</span>
                        </div>
                        <div style="font-size: 12.5px; color: var(--ws-text-secondary); margin-top: 2px;">
                            Drop a completed PDF rental application here or click to browse — automatically reads handwritten, printed, or typed fields &amp; signature.
                        </div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <button type="button" id="pdf-import-preview-toggle" class="btn btn-outline btn-xs" title="Open imported PDF in a separate movable window (can be dragged to a 2nd monitor)" style="display: none; padding: 6px 12px; font-size: 12.5px; font-weight: 600;">
                        👁️ View Imported PDF &#8599;
                    </button>
                    <button type="button" id="pdf-import-browse-btn" class="btn btn-primary btn-sm" style="padding: 7px 14px; font-size: 13px; font-weight: 600;">
                        📄 Select PDF to Import
                    </button>
                </div>
            </div>

            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--ws-border-subtle); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                <label style="display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--ws-text-secondary); cursor: pointer; margin: 0;">
                    <input type="checkbox" id="pdf-import-attach-doc" checked style="width: 15px; height: 15px; accent-color: var(--ws-accent);">
                    <span>Also attach imported PDF to <strong>Documents &amp; Identification</strong></span>
                </label>
                <span style="font-size: 11.5px; color: var(--ws-text-muted);">Supports multi-page scanned images, stylus forms &amp; digital PDFs</span>
            </div>

            <!-- Extraction Progress & Status Bar -->
            <div id="pdf-import-status" style="display: none; margin-top: 12px;">
                <div style="height: 6px; width: 100%; background: rgba(148, 163, 184, 0.2); border-radius: 999px; overflow: hidden; margin-bottom: 6px;">
                    <div id="pdf-import-progress-bar" style="height: 100%; width: 0%; background: #2563eb; transition: width 0.25s ease, background 0.25s ease;"></div>
                </div>
                <div id="pdf-import-status-text" style="font-size: 12.5px; font-weight: 600; color: var(--ws-text-primary);"></div>
            </div>
        </div>

        <!-- Fallback In-Page Carousel Modal Window (with Pop-Out to 2nd Display button) -->
        <div id="pdf-preview-modal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: var(--modal-overlay, rgba(18, 18, 18, 0.55)); backdrop-filter: blur(3px); align-items: center; justify-content: center; padding: 16px;">
            <div id="pdf-preview-modal-dialog" class="card" style="background: var(--ws-surface); border: 1px solid var(--ws-border); border-radius: var(--radius-md, 14px); width: 96vw; max-width: 1050px; height: 94vh; display: flex; flex-direction: column; box-shadow: var(--shadow-lg); overflow: hidden; margin: 0;">
                <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 12px 18px; background: var(--table-th-bg); border-bottom: 1px solid var(--ws-border);">
                    <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                        <span style="font-size: 18px;">📄</span>
                        <h3 id="pdf-preview-modal-title" class="card-title" style="margin: 0; font-size: 15px; font-weight: 700; color: var(--ws-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            Imported PDF Application
                        </h3>
                    </div>

                    <!-- Carousel Page Controls -->
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <button type="button" id="pdf-carousel-prev" class="btn btn-outline btn-xs" style="padding: 6px 13px; font-size: 12.5px; font-weight: 700; border-radius: 8px; background: var(--ws-surface); border: 1.5px solid var(--ws-border-strong); box-shadow: var(--shadow-sm);">&#9664; Prev</button>
                        <div style="display: inline-flex; align-items: center; gap: 8px; background: var(--ws-bg); border: 1px solid var(--ws-border-strong); padding: 3px 10px 3px 4px; border-radius: 999px;">
                            <div id="pdf-carousel-dots" style="display: flex; align-items: center; gap: 4px;"></div>
                            <span id="pdf-carousel-page-indicator" style="font-size: 11.5px; font-weight: 700; color: var(--ws-text-secondary); white-space: nowrap;">Page 1 of 1</span>
                        </div>
                        <button type="button" id="pdf-carousel-next" class="btn btn-outline btn-xs" style="padding: 6px 13px; font-size: 12.5px; font-weight: 700; border-radius: 8px; background: var(--ws-surface); border: 1.5px solid var(--ws-border-strong); box-shadow: var(--shadow-sm);">Next &#9654;</button>
                    </div>

                    <!-- Fit Group (Separated) + Zoom Group + Pop-Out / Close Controls -->
                    <div style="display: flex; align-items: center; gap: 14px; flex-shrink: 0; flex-wrap: wrap;">
                        <!-- Distinct Fit Mode Group -->
                        <div style="display: inline-flex; align-items: center; gap: 4px; background: var(--ws-bg); border: 1px solid var(--ws-border-strong); padding: 3px; border-radius: 9px;">
                            <button type="button" id="pdf-modal-fit-page" class="btn btn-primary btn-xs" title="Fit Entire Page in Window" style="padding: 4px 10px; font-size: 12px; font-weight: 600; border-radius: 6px;">Fit Page</button>
                            <button type="button" id="pdf-modal-fit-width" class="btn btn-outline btn-xs" title="Fit Page Width" style="padding: 4px 10px; font-size: 12px; font-weight: 600; border-radius: 6px; border-color: transparent;">Fit Width</button>
                        </div>

                        <span style="width: 1px; height: 22px; background: var(--ws-border-strong); display: inline-block;"></span>

                        <!-- Distinct Zoom Stepper Group -->
                        <div style="display: inline-flex; align-items: center; gap: 4px; background: var(--ws-bg); border: 1px solid var(--ws-border-strong); padding: 3px 6px; border-radius: 9px;">
                            <button type="button" id="pdf-modal-zoom-out" class="btn btn-outline btn-xs" title="Zoom Out" style="padding: 3px 9px; font-size: 13px; font-weight: 700; border-radius: 6px;">&minus;</button>
                            <span id="pdf-modal-zoom-label" style="font-size: 12px; font-weight: 700; min-width: 46px; text-align: center; color: var(--ws-text);">Fit</span>
                            <button type="button" id="pdf-modal-zoom-in" class="btn btn-outline btn-xs" title="Zoom In" style="padding: 3px 9px; font-size: 13px; font-weight: 700; border-radius: 6px;">&#43;</button>
                        </div>

                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <button type="button" id="pdf-modal-popout-btn" class="btn btn-outline btn-xs" title="Open in a separate desktop window to drag to your 2nd monitor" style="padding: 5px 11px; font-size: 12px; font-weight: 600;">
                                &#8599; 2nd Display Window
                            </button>
                            <button type="button" id="pdf-preview-modal-close" class="btn btn-outline btn-danger btn-xs" style="padding: 5px 12px; font-size: 12.5px; font-weight: 700;">
                                &times; Close
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 1-Page-at-a-Time Carousel Stage -->
                <div style="position: relative; flex: 1; background: var(--ws-bg); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <button type="button" id="pdf-stage-prev-arrow" class="btn btn-outline" title="Previous Page (Left Arrow)" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); z-index: 10; width: 42px; height: 42px; border-radius: 50%; background: var(--ws-surface); border: 1px solid var(--ws-border-strong); color: var(--ws-text); font-size: 22px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-md); padding: 0;">&lsaquo;</button>

                    <div id="pdf-import-pages-grid" style="width: 100%; height: 100%; overflow: auto; padding: 16px 68px; display: flex; align-items: flex-start; justify-content: center;"></div>

                    <button type="button" id="pdf-stage-next-arrow" class="btn btn-outline" title="Next Page (Right Arrow)" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); z-index: 10; width: 42px; height: 42px; border-radius: 50%; background: var(--ws-surface); border: 1px solid var(--ws-border-strong); color: var(--ws-text); font-size: 22px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-md); padding: 0;">&rsaquo;</button>
                </div>
            </div>
        </div>

        <form action="<?= Router::url('/applicants') ?>" method="POST" enctype="multipart/form-data">
            
            <!-- Property & Unit -->
            <h3 style="font-size: 16px; font-weight: 700; border-bottom: 2px solid var(--ws-border-subtle); padding-bottom: 8px; margin-bottom: 16px;">Property & Unit</h3>

            <div style="background: var(--ws-surface-subtle); border: 1px solid var(--ws-border-subtle); padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="target_property_id">Property</label>
                        <select id="target_property_id" name="target_property_id" class="form-control" onchange="filterUnitsByProperty(this.value)">
                            <option value="">-- Select Property --</option>
                            <?php foreach ($properties as $prop): ?>
                                <option value="<?= $prop['id'] ?>"><?= htmlspecialchars($prop['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-6">
                        <label for="target_unit_id">Unit</label>
                        <select id="target_unit_id" name="target_unit_id" class="form-control">
                            <option value="">-- Select Unit --</option>
                            <?php foreach ($allUnits as $u): ?>
                                <option value="<?= $u['id'] ?>" 
                                        data-property-id="<?= $u['property_id'] ?>"
                                        data-rent="<?= $u['target_rent'] ?>">
                                    <?= htmlspecialchars($u['unit_number']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Accommodation Information -->
            <h3 style="font-size: 16px; font-weight: 700; border-bottom: 2px solid var(--ws-border-subtle); padding-bottom: 8px; margin-top: 24px; margin-bottom: 16px;">Accommodation Information</h3>
            
            <div style="background: var(--ws-surface-subtle); border: 1px solid var(--ws-border-subtle); padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="date_requested">Date Requested</label>
                        <input type="date" id="date_requested" name="date_requested" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group col-6">
                        <label for="occupants_children">Names of Children Occupying</label>
                        <input type="text" id="occupants_children" name="occupants_children" class="form-control" placeholder="e.g. Timmy (8), Sarah (5)">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="occupants_adults">Names of Adults Occupying</label>
                    <textarea id="occupants_adults" name="occupants_adults" class="form-control auto-expand" rows="2" placeholder="List all adult occupants" style="min-height: 52px; resize: vertical; line-height: 1.5;"></textarea>
                </div>
            </div>

            <!-- Term of Tenancy -->
            <h3 style="font-size: 16px; font-weight: 700; border-bottom: 2px solid var(--ws-border-subtle); padding-bottom: 8px; margin-top: 24px; margin-bottom: 16px;">Term of Tenancy</h3>

            <div style="background: var(--ws-surface-subtle); border: 1px solid var(--ws-border-subtle); padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                <div class="form-row" style="margin-bottom: 0;">
                    <div class="form-group col-4" style="margin-bottom: 0;">
                        <label for="tenancy_type">Tenancy Type *</label>
                        <select id="tenancy_type" name="tenancy_type" class="form-control" onchange="toggleTenancyFields(this.value)">
                            <option value="fixed" selected>Fixed Term</option>
                            <option value="periodic">Periodic</option>
                        </select>
                    </div>
                    <div class="form-group col-4" style="margin-bottom: 0;">
                        <label for="term_start_date">Beginning Date</label>
                        <input type="date" id="term_start_date" name="term_start_date" class="form-control" onchange="autoCalculateEndDate(this.value)">
                    </div>
                    <div class="form-group col-4" id="term_end_date_group" style="margin-bottom: 0;">
                        <label for="term_end_date">Ending Date</label>
                        <input type="date" id="term_end_date" name="term_end_date" class="form-control">
                    </div>
                </div>
            </div>

            <!-- Personal Information of Applicant -->
            <h3 style="font-size: 16px; font-weight: 700; border-bottom: 2px solid var(--ws-border-subtle); padding-bottom: 8px; margin-top: 24px; margin-bottom: 16px;">Personal Information of Applicant</h3>

            <div style="background: var(--ws-surface-subtle); border: 1px solid var(--ws-border-subtle); padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" required placeholder="First name">
                    </div>
                    <div class="form-group col-4">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name" class="form-control" placeholder="Middle name (optional)">
                    </div>
                    <div class="form-group col-4">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" required placeholder="Last name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="birthday">Birthday</label>
                        <input type="date" id="birthday" name="birthday" class="form-control">
                    </div>
                    <div class="form-group col-6">
                        <label for="driver_license">Driver License #</label>
                        <input type="text" id="driver_license" name="driver_license" class="form-control" placeholder="License number">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="cell_phone">Cell Phone *</label>
                        <input type="tel" id="cell_phone" name="cell_phone" class="form-control" placeholder="780-123-4567" maxlength="12">
                    </div>
                    <div class="form-group col-4">
                        <label for="home_phone">Home Phone</label>
                        <input type="tel" id="home_phone" name="home_phone" class="form-control" placeholder="780-123-4567" maxlength="12">
                    </div>
                    <div class="form-group col-4">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="applicant@email.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-8">
                        <label for="present_address">Present Address</label>
                        <input type="text" id="present_address" name="present_address" class="form-control" placeholder="Street, City, Province, Postal Code">
                    </div>
                    <div class="form-group col-4">
                        <label for="present_address_duration">How long?</label>
                        <input type="text" id="present_address_duration" name="present_address_duration" class="form-control" placeholder="e.g. 2 years 3 months">
                    </div>
                </div>

                <div class="form-row" style="margin-bottom: 0;">
                    <div class="form-group col-8" style="margin-bottom: 0;">
                        <label for="previous_address">Previous Address</label>
                        <input type="text" id="previous_address" name="previous_address" class="form-control" placeholder="Street, City, Province, Postal Code">
                    </div>
                    <div class="form-group col-4" style="margin-bottom: 0;">
                        <label for="previous_address_duration">How long?</label>
                        <input type="text" id="previous_address_duration" name="previous_address_duration" class="form-control" placeholder="e.g. 1 year">
                    </div>
                </div>
            </div>

            <!-- Employment Information of Applicant -->
            <h3 style="font-size: 16px; font-weight: 700; border-bottom: 2px solid var(--ws-border-subtle); padding-bottom: 8px; margin-top: 24px; margin-bottom: 16px;">Employment Information of Applicant</h3>

            <!-- Current Employment -->
            <div style="background: var(--ws-surface-subtle); padding: 16px; border-radius: 12px; border: 1px solid var(--ws-border-subtle); margin-bottom: 16px;">
                <h4 style="font-size: 14px; font-weight: 700; margin-top: 0; margin-bottom: 14px; border-bottom: 1.5px solid var(--ws-border-subtle); padding-bottom: 8px;">Current Employment</h4>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="current_employer">Current Employer</label>
                        <input type="text" id="current_employer" name="current_employer" class="form-control" placeholder="Company or Business Name">
                    </div>
                    <div class="form-group col-4">
                        <label for="current_occupation">Occupation</label>
                        <input type="text" id="current_occupation" name="current_occupation" class="form-control" placeholder="e.g. Electrician, Accountant">
                    </div>
                    <div class="form-group col-4">
                        <label for="current_employment_duration">How long?</label>
                        <input type="text" id="current_employment_duration" name="current_employment_duration" class="form-control" placeholder="e.g. 3 years 2 months">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="current_supervisor">Supervisor Name</label>
                        <input type="text" id="current_supervisor" name="current_supervisor" class="form-control" placeholder="Supervisor Full Name">
                    </div>
                    <div class="form-group col-6">
                        <label for="current_employer_phone">Supervisor Work Phone</label>
                        <input type="tel" id="current_employer_phone" name="current_employer_phone" class="form-control" placeholder="780-123-4567" maxlength="12">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="current_employer_address">Employer Address</label>
                    <input type="text" id="current_employer_address" name="current_employer_address" class="form-control" placeholder="Street, City, Province, Postal Code">
                </div>
            </div>

            <!-- Previous Employment -->
            <div style="background: var(--ws-surface-subtle); padding: 16px; border-radius: 12px; border: 1px solid var(--ws-border-subtle); margin-bottom: 24px;">
                <h4 style="font-size: 14px; font-weight: 700; margin-top: 0; margin-bottom: 14px; border-bottom: 1.5px solid var(--ws-border-subtle); padding-bottom: 8px;">Previous Employment</h4>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="previous_employer">Previous Employer</label>
                        <input type="text" id="previous_employer" name="previous_employer" class="form-control" placeholder="Company or Business Name">
                    </div>
                    <div class="form-group col-4">
                        <label for="previous_occupation">Occupation</label>
                        <input type="text" id="previous_occupation" name="previous_occupation" class="form-control" placeholder="e.g. Electrician, Accountant">
                    </div>
                    <div class="form-group col-4">
                        <label for="previous_employment_duration">How long?</label>
                        <input type="text" id="previous_employment_duration" name="previous_employment_duration" class="form-control" placeholder="e.g. 1 year 6 months">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="previous_supervisor">Supervisor Name</label>
                        <input type="text" id="previous_supervisor" name="previous_supervisor" class="form-control" placeholder="Supervisor Full Name">
                    </div>
                    <div class="form-group col-6">
                        <label for="previous_employer_phone">Supervisor Work Phone</label>
                        <input type="tel" id="previous_employer_phone" name="previous_employer_phone" class="form-control" placeholder="780-123-4567" maxlength="12">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="previous_employer_address">Employer Address</label>
                    <input type="text" id="previous_employer_address" name="previous_employer_address" class="form-control" placeholder="Street, City, Province, Postal Code">
                </div>
            </div>

            <!-- Income Information -->
            <h3 style="font-size: 16px; font-weight: 700; border-bottom: 2px solid var(--ws-border-subtle); padding-bottom: 8px; margin-top: 24px; margin-bottom: 16px;">Income Information of Applicant</h3>

            <div style="background: var(--ws-surface-subtle); border: 1px solid var(--ws-border-subtle); padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                <div class="form-row" style="margin-bottom: 0;">
                    <div class="form-group col-6" style="margin-bottom: 0;">
                        <label for="monthly_income">Income ($ per month)</label>
                        <input type="number" id="monthly_income" name="monthly_income" class="form-control" step="0.01" placeholder="e.g. 4500.00">
                    </div>
                </div>
            </div>

            <!-- Rental History -->
            <h3 style="font-size: 16px; font-weight: 700; border-bottom: 2px solid var(--ws-border-subtle); padding-bottom: 8px; margin-top: 24px; margin-bottom: 16px;">Rental History</h3>

            <div style="background: var(--ws-surface-subtle); border: 1px solid var(--ws-border-subtle); padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="current_landlord_name">Current Landlord's Name</label>
                        <input type="text" id="current_landlord_name" name="current_landlord_name" class="form-control" placeholder="Landlord or Property Manager Name">
                    </div>
                    <div class="form-group col-6">
                        <label for="current_landlord_phone">Current Landlord's Phone</label>
                        <input type="tel" id="current_landlord_phone" name="current_landlord_phone" class="form-control" placeholder="780-123-4567" maxlength="12">
                    </div>
                </div>

                <div class="form-row" style="margin-bottom: 0;">
                    <div class="form-group col-6" style="margin-bottom: 0;">
                        <label for="previous_landlord_name">Previous Landlord's Name</label>
                        <input type="text" id="previous_landlord_name" name="previous_landlord_name" class="form-control" placeholder="Landlord or Property Manager Name">
                    </div>
                    <div class="form-group col-6" style="margin-bottom: 0;">
                        <label for="previous_landlord_phone">Previous Landlord's Phone</label>
                        <input type="tel" id="previous_landlord_phone" name="previous_landlord_phone" class="form-control" placeholder="780-123-4567" maxlength="12">
                    </div>
                </div>
            </div>

            <!-- Relatives or Friends in Case of Emergency -->
            <h3 style="font-size: 16px; font-weight: 700; border-bottom: 2px solid var(--ws-border-subtle); padding-bottom: 8px; margin-top: 24px; margin-bottom: 16px;">Relatives or Friends who can be Contacted in Case of Emergency</h3>

            <!-- Contact #1 -->
            <div style="background: var(--ws-surface-subtle); padding: 16px; border-radius: 12px; border: 1px solid var(--ws-border-subtle); margin-bottom: 16px;">
                <h4 style="font-size: 14px; font-weight: 700; margin-top: 0; margin-bottom: 14px; border-bottom: 1.5px solid var(--ws-border-subtle); padding-bottom: 8px;">Contact #1</h4>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="emergency_contact_1_name">Name</label>
                        <input type="text" id="emergency_contact_1_name" name="emergency_contact_1_name" class="form-control" placeholder="Full Name">
                    </div>
                    <div class="form-group col-4">
                        <label for="emergency_contact_1_relationship">Relationship</label>
                        <input type="text" id="emergency_contact_1_relationship" name="emergency_contact_1_relationship" class="form-control" placeholder="e.g. Parent, Sibling, Friend">
                    </div>
                    <div class="form-group col-4">
                        <label for="emergency_contact_1_phone">Phone</label>
                        <input type="tel" id="emergency_contact_1_phone" name="emergency_contact_1_phone" class="form-control" placeholder="780-123-4567" maxlength="12">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="emergency_contact_1_address">Address</label>
                    <input type="text" id="emergency_contact_1_address" name="emergency_contact_1_address" class="form-control" placeholder="Street, City, Province, Postal Code">
                </div>
            </div>

            <!-- Contact #2 -->
            <div style="background: var(--ws-surface-subtle); padding: 16px; border-radius: 12px; border: 1px solid var(--ws-border-subtle); margin-bottom: 24px;">
                <h4 style="font-size: 14px; font-weight: 700; margin-top: 0; margin-bottom: 14px; border-bottom: 1.5px solid var(--ws-border-subtle); padding-bottom: 8px;">Contact #2</h4>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="emergency_contact_2_name">Name</label>
                        <input type="text" id="emergency_contact_2_name" name="emergency_contact_2_name" class="form-control" placeholder="Full Name">
                    </div>
                    <div class="form-group col-4">
                        <label for="emergency_contact_2_relationship">Relationship</label>
                        <input type="text" id="emergency_contact_2_relationship" name="emergency_contact_2_relationship" class="form-control" placeholder="e.g. Colleague, Relative, Friend">
                    </div>
                    <div class="form-group col-4">
                        <label for="emergency_contact_2_phone">Phone</label>
                        <input type="tel" id="emergency_contact_2_phone" name="emergency_contact_2_phone" class="form-control" placeholder="780-123-4567" maxlength="12">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="emergency_contact_2_address">Address</label>
                    <input type="text" id="emergency_contact_2_address" name="emergency_contact_2_address" class="form-control" placeholder="Street, City, Province, Postal Code">
                </div>
            </div>

            <!-- Authorization, Consent & Signature -->
            <h3 style="font-size: 16px; font-weight: 700; border-bottom: 2px solid var(--ws-border-subtle); padding-bottom: 8px; margin-top: 24px; margin-bottom: 16px;">Declaration & Authorization</h3>

            <div style="background: var(--ws-surface-subtle); border: 1px solid var(--ws-border-subtle); padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                <p style="font-size: 13.5px; line-height: 1.6; margin-top: 0; margin-bottom: 12px;">
                    <em>Approval of this application is subject to Landlord and Tenant signing a Residential Tenancy Agreement. All statements that I made in this application are true. I authorize the Landlord to do a credit check.</em>
                </p>
                <div style="margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--ws-border-subtle);">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
                        <input type="checkbox" name="credit_check_authorized" value="1" checked style="width: 17px; height: 17px; accent-color: var(--ws-accent);">
                        Applicant authorizes credit check and confirms statements are true
                    </label>
                </div>

                <div class="form-row" style="margin-bottom: 14px;">
                    <div class="form-group col-6" style="margin-bottom: 0;">
                        <label for="application_date">Application Date</label>
                        <input type="date" id="application_date" name="application_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <!-- Interactive e-Signature Pad (Mouse, Stylus, Touch & Type-to-Sign) -->
                <div class="form-group" style="margin-bottom: 0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label for="esig-canvas" style="margin: 0; font-weight: 700;">Applicant e-Signature</label>
                            <span id="esig-status-badge" style="font-size: 11.5px; font-weight: 600; padding: 2px 8px; border-radius: 999px; border: 1px solid rgba(100, 116, 139, 0.25); background: rgba(100, 116, 139, 0.1); color: #64748b;">
                                Awaiting Signature
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <button type="button" id="esig-mode-draw-btn" class="btn btn-outline btn-xs active" style="padding: 4px 10px; font-size: 12px;">
                                ✍️ Draw (Mouse / Stylus)
                            </button>
                            <button type="button" id="esig-mode-type-btn" class="btn btn-outline btn-xs" style="padding: 4px 10px; font-size: 12px;">
                                ⌨️ Type Name
                            </button>
                            <button type="button" id="esig-undo-btn" class="btn btn-outline btn-xs" title="Undo last stroke" style="padding: 4px 10px; font-size: 12px;">
                                ↩️ Undo
                            </button>
                            <button type="button" id="esig-clear-btn" class="btn btn-outline btn-danger btn-xs" title="Clear signature pad" style="padding: 4px 10px; font-size: 12px;">
                                🧹 Clear
                            </button>
                        </div>
                    </div>

                    <div id="esig-type-panel" style="display: none; margin-bottom: 10px;">
                        <input type="text" id="esig-type-input" class="form-control" placeholder="Type full legal name to generate cursive e-signature..." autocomplete="off">
                    </div>

                    <div style="position: relative; width: 100%; height: 150px; background: #ffffff; border: 1.5px solid var(--ws-border); border-radius: 10px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.04);">
                        <canvas id="esig-canvas" style="width: 100%; height: 100%; display: block; cursor: crosshair; touch-action: none;"></canvas>

                        <!-- Signature Baseline & X Mark -->
                        <div style="position: absolute; left: 20px; right: 20px; bottom: 28px; border-bottom: 1.5px dashed #cbd5e1; pointer-events: none; display: flex; align-items: flex-end;">
                            <span style="color: #94a3b8; font-size: 15px; font-weight: 700; margin-bottom: 2px; user-select: none;">&times;</span>
                        </div>

                        <!-- Placeholder Hint -->
                        <div id="esig-placeholder" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; color: #94a3b8; font-size: 13.5px; font-style: italic; transition: opacity 0.2s ease; user-select: none;">
                            Sign here using your mouse, trackpad, or stylus
                        </div>
                    </div>

                    <input type="hidden" id="applicant_signature" name="applicant_signature" value="">
                </div>
            </div>

            <!-- Documents & Identification -->
            <div class="form-group" style="margin-top: 24px; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; border-bottom: 2px solid var(--ws-border-subtle); padding-bottom: 8px;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700;">Documents & Identification</h3>
                    <label for="applicant_documents" title="Add Documents (Driver's License, Photo ID, etc.)" 
                           style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: transparent; border: 2px solid var(--ws-accent); color: var(--ws-accent); cursor: pointer; transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease; padding: 0;"
                           onmouseover="this.style.transform='scale(1.12)'; this.style.background='var(--ws-accent-subtle)';"
                           onmouseout="this.style.transform='scale(1)'; this.style.background='transparent';">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="display: block;">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </label>
                </div>
                
                <input type="file" id="applicant_documents" name="applicant_documents[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" onchange="handleNewApplicantDocuments(this)" style="display: none;">

                <div id="applicant-documents-grid" style="display: none; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 14px; margin-bottom: 16px;">
                </div>

                <div id="applicant-documents-empty" style="text-align: center; padding: 22px 14px; color: var(--ws-text-muted); font-size: 13.5px; border: 1.5px dashed var(--ws-border-subtle); border-radius: 10px;">
                    No documents attached yet. Click the <span style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 50%; border: 1.5px solid var(--ws-accent); color: var(--ws-accent); font-size: 12px; font-weight: 700; line-height: 1; vertical-align: middle;">+</span> button above to upload photo ID, driver's license, or paystubs.
                </div>
            </div>

            <hr style="border: 0; border-top: 2px solid var(--ws-border-subtle); margin: 28px 0 24px 0;">

            <!-- Internal Landlord Notes -->
            <div class="form-group" style="margin-top: 24px; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; border-bottom: 2px solid var(--ws-border-subtle); padding-bottom: 8px;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700;">Internal Landlord Notes</h3>
                </div>
                <div style="background: var(--ws-surface-subtle); border: 1px solid var(--ws-border-subtle); padding: 16px; border-radius: 12px;">
                    <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Notes, interview observations, or conditions..." style="margin-bottom: 0;"></textarea>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 24px;">
                <a href="<?= Router::url('/applicants') ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Submit Application</button>
            </div>
        </form>
    </div>
</div>

<script>
function autoCalculateEndDate(startDateVal) {
    if (!startDateVal) return;
    const tenancySelect = document.getElementById('tenancy_type');
    if (tenancySelect && tenancySelect.value !== 'fixed') return;

    // Parse YYYY-MM-DD cleanly using integer parts to prevent timezone offsets
    const parts = startDateVal.split('-');
    if (parts.length !== 3) return;

    const startYear = parseInt(parts[0], 10);
    const startMonth = parseInt(parts[1], 10); // 1-12
    const startDay = parseInt(parts[2], 10);   // 1-31

    let endYear, endMonth, endDay;

    if (startDay === 1) {
        // Example: Dec 1, 2026 -> 12-month lease ends on last day of 11th month (Nov 30, 2027)
        // In JS Date, (year, month - 1, 0) gives the last day of the preceding month
        // 12 months forward from startYear/startMonth is startYear + 1, startMonth.
        // The last day of the prior month is day 0 of month (startMonth - 1).
        const targetDate = new Date(startYear + 1, startMonth - 1, 0);
        endYear = targetDate.getFullYear();
        endMonth = String(targetDate.getMonth() + 1).padStart(2, '0');
        endDay = String(targetDate.getDate()).padStart(2, '0');
    } else {
        // Standard 12-month lease ending the day before: e.g. Oct 15, 2026 -> Oct 14, 2027
        const targetDate = new Date(startYear + 1, startMonth - 1, startDay - 1);
        endYear = targetDate.getFullYear();
        endMonth = String(targetDate.getMonth() + 1).padStart(2, '0');
        endDay = String(targetDate.getDate()).padStart(2, '0');
    }

    const endDateInput = document.getElementById('term_end_date');
    if (endDateInput) {
        endDateInput.value = `${endYear}-${endMonth}-${endDay}`;
    }
}

function toggleTenancyFields(type) {
    const endGroup = document.getElementById('term_end_date_group');
    if (type === 'fixed') {
        endGroup.style.display = 'block';
        const startVal = document.getElementById('term_start_date').value;
        if (startVal) autoCalculateEndDate(startVal);
    } else {
        endGroup.style.display = 'none';
    }
}

function filterUnitsByProperty(propertyId) {
    const unitSelect = document.getElementById('target_unit_id');
    if (!unitSelect) return;
    const options = unitSelect.querySelectorAll('option');

    options.forEach(opt => {
        if (!opt.value) {
            opt.style.display = 'block';
            return;
        }
        const unitPropId = opt.getAttribute('data-property-id');
        // Only show units when a property is selected; hide all if no property is selected
        if (propertyId && unitPropId === propertyId) {
            opt.style.display = 'block';
        } else {
            opt.style.display = 'none';
            if (opt.selected) {
                opt.selected = false;
            }
        }
    });

    // If current selected is hidden, reset to default
    const currentOpt = unitSelect.options[unitSelect.selectedIndex];
    if (currentOpt && currentOpt.style.display === 'none') {
        unitSelect.value = '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const propSelect = document.getElementById('target_property_id');
    filterUnitsByProperty(propSelect ? propSelect.value : '');
});

// Document Management & Lightbox for New Applicant
let newDocDataTransfer = null;
try {
    newDocDataTransfer = new DataTransfer();
} catch (e) {
    newDocDataTransfer = null;
}
let currentDocIndex = 0;
let createdDocUrls = [];

function handleNewApplicantDocuments(inputElem) {
    if (!inputElem.files || inputElem.files.length === 0) return;

    if (newDocDataTransfer) {
        Array.from(inputElem.files).forEach(file => {
            newDocDataTransfer.items.add(file);
        });
        inputElem.files = newDocDataTransfer.files;
    }
    renderNewApplicantDocs();
}

function removeNewApplicantDoc(index) {
    const inputElem = document.getElementById('applicant_documents');
    if (newDocDataTransfer) {
        newDocDataTransfer.items.remove(index);
        if (inputElem) {
            inputElem.files = newDocDataTransfer.files;
        }
    }
    renderNewApplicantDocs();
}

function renderNewApplicantDocs() {
    const inputElem = document.getElementById('applicant_documents');
    const grid = document.getElementById('applicant-documents-grid');
    const emptyNotice = document.getElementById('applicant-documents-empty');
    if (!grid || !emptyNotice || !inputElem) return;

    // Revoke previous blob URLs to prevent memory leaks
    createdDocUrls.forEach(url => URL.revokeObjectURL(url));
    createdDocUrls = [];

    const files = Array.from(inputElem.files || []);
    grid.innerHTML = '';

    if (files.length === 0) {
        grid.style.display = 'none';
        emptyNotice.style.display = 'block';
        return;
    }

    grid.style.display = 'grid';
    emptyNotice.style.display = 'none';

    files.forEach((file, idx) => {
        const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
        const fileUrl = URL.createObjectURL(file);
        createdDocUrls.push(fileUrl);

        const card = document.createElement('div');
        card.className = 'applicant-doc-card';
        card.style.cssText = 'position: relative; border-radius: 12px; overflow: hidden; border: 1px solid var(--ws-border-subtle); background: var(--ws-surface-subtle); padding: 6px; text-align: center; user-select: none; transition: opacity 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;';

        if (isPdf) {
            card.innerHTML = `
                <a href="${fileUrl}" target="_blank" title="Preview PDF Document" 
                   style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 95px; background: rgba(239, 68, 68, 0.08); border-radius: 8px; text-decoration: none; color: #dc2626; transition: transform 0.15s ease;"
                   onmouseover="this.style.transform='scale(1.03)'"
                   onmouseout="this.style.transform='scale(1)'">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span style="font-size: 11px; font-weight: 700; margin-top: 4px; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        ${escapeHtml(file.name)}
                    </span>
                </a>
            `;
        } else {
            card.innerHTML = `
                <img src="${fileUrl}" 
                     alt="Applicant Document" 
                     onclick="openDocModal('${fileUrl}')" 
                     style="width: 100%; height: 95px; object-fit: cover; border-radius: 8px; cursor: pointer; transition: transform 0.15s ease;"
                     draggable="false"
                     onmouseover="this.style.transform='scale(1.03)'"
                     onmouseout="this.style.transform='scale(1)'">
            `;
        }

        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.title = 'Remove document';
        delBtn.style.cssText = 'position: absolute; top: 10px; right: 10px; width: 26px; height: 26px; border-radius: 50%; background: rgba(0,0,0,0.65); border: none; color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.15s ease, transform 0.15s ease; z-index: 5;';
        delBtn.onmouseover = function() { this.style.background='#dc2626'; this.style.transform='scale(1.1)'; };
        delBtn.onmouseout = function() { this.style.background='rgba(0,0,0,0.65)'; this.style.transform='scale(1)'; };
        delBtn.innerHTML = `
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                <line x1="10" y1="11" x2="10" y2="17"></line>
                <line x1="14" y1="11" x2="14" y2="17"></line>
            </svg>
        `;
        delBtn.onclick = function() {
            removeNewApplicantDoc(idx);
        };
        card.appendChild(delBtn);

        grid.appendChild(card);
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getAllDocSources() {
    const images = document.querySelectorAll('#applicant-documents-grid .applicant-doc-card img');
    return Array.from(images).map(img => img.getAttribute('src'));
}

function openDocModal(src) {
    const docs = getAllDocSources();
    currentDocIndex = docs.indexOf(src);
    if (currentDocIndex === -1) currentDocIndex = 0;
    updateDocModalView();
    document.getElementById('applicant-doc-modal').classList.add('active');
}

function updateDocModalView() {
    const docs = getAllDocSources();
    if (docs.length === 0) return;
    
    if (currentDocIndex >= docs.length) currentDocIndex = 0;
    if (currentDocIndex < 0) currentDocIndex = docs.length - 1;

    const img = document.getElementById('modal-doc-img');
    if (img) img.src = docs[currentDocIndex];

    const counter = document.getElementById('modal-doc-counter');
    if (counter) {
        counter.textContent = (currentDocIndex + 1) + ' / ' + docs.length;
    }
}

function navigateDocModal(direction, e) {
    if (e) e.stopPropagation();
    currentDocIndex += direction;
    updateDocModalView();
}

function closeDocModal(e) {
    if (e.target.id === 'applicant-doc-modal') {
        document.getElementById('applicant-doc-modal').classList.remove('active');
    }
}

document.addEventListener('keydown', (e) => {
    const modal = document.getElementById('applicant-doc-modal');
    if (!modal || !modal.classList.contains('active')) return;
    if (e.key === 'ArrowRight') {
        navigateDocModal(1);
    } else if (e.key === 'ArrowLeft') {
        navigateDocModal(-1);
    } else if (e.key === 'Escape') {
        modal.classList.remove('active');
    }
});
</script>

<!-- Lightbox Modal for Document Preview -->
<div id="applicant-doc-modal" class="modal-backdrop" onclick="closeDocModal(event)">
    <div style="position: relative; max-width: 90vw; max-height: 90vh; display: flex; align-items: center; justify-content: center;">
        <button type="button" onclick="document.getElementById('applicant-doc-modal').classList.remove('active')" 
                style="position: absolute; top: -45px; right: 0; background: none; border: none; color: #fff; font-size: 34px; cursor: pointer; line-height: 1;">&times;</button>
        
        <!-- Previous Arrow -->
        <button type="button" onclick="navigateDocModal(-1, event)" 
                title="Previous document"
                style="position: absolute; left: -50px; background: rgba(0,0,0,0.6); border: 1.5px solid rgba(255,255,255,0.3); color: #fff; width: 44px; height: 44px; border-radius: 50%; font-size: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s ease, transform 0.15s ease; z-index: 10;"
                onmouseover="this.style.background='var(--ws-accent)'; this.style.transform='scale(1.08)';"
                onmouseout="this.style.background='rgba(0,0,0,0.6)'; this.style.transform='scale(1)';">
            &#10094;
        </button>

        <img id="modal-doc-img" src="" alt="Document Preview" style="max-width: 82vw; max-height: 82vh; border-radius: 14px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); object-fit: contain; transition: opacity 0.15s ease;">

        <!-- Next Arrow -->
        <button type="button" onclick="navigateDocModal(1, event)" 
                title="Next document"
                style="position: absolute; right: -50px; background: rgba(0,0,0,0.6); border: 1.5px solid rgba(255,255,255,0.3); color: #fff; width: 44px; height: 44px; border-radius: 50%; font-size: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s ease, transform 0.15s ease; z-index: 10;"
                onmouseover="this.style.background='var(--ws-accent)'; this.style.transform='scale(1.08)';"
                onmouseout="this.style.background='rgba(0,0,0,0.6)'; this.style.transform='scale(1)';">
            &#10095;
        </button>

        <!-- Document Counter Badge -->
        <div id="modal-doc-counter" style="position: absolute; bottom: -36px; color: #fff; font-size: 13px; font-weight: 500; background: rgba(0,0,0,0.5); padding: 4px 12px; border-radius: 20px;"></div>
    </div>
</div>
