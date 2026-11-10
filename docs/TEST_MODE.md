# PropPulse PMS — Development Test Mode & Auto-Fill Guide

The **Development Test Mode** system allows developers and QA testers to quickly populate application forms with realistic test data (either randomized or deterministic presets) with a single selection or click.

---

## 1. Production Safety & Environment Gate

Test Mode is **strictly restricted to non-production environments** and is controlled in [`config/app.php`](../config/app.php):

```php
// config/app.php
$appEnv = getenv('APP_ENV') ?: 'development'; // 'development', 'testing', or 'production'
if (!defined('APP_ENV')) {
    define('APP_ENV', $appEnv);
}
if (!defined('TEST_MODE_AVAILABLE')) {
    define('TEST_MODE_AVAILABLE', in_array(APP_ENV, ['development', 'testing', 'local'], true));
}
```

### How to Disable for Production
1. **Via Environment Variable**: Set `APP_ENV=production` in your server/Apache environment.
2. **Via `config/app.php`**: Change `$appEnv = 'production';`.
3. **Optional Deployment Exclusion**: Because [`views/layouts/footer.php`](../views/layouts/footer.php) also checks `file_exists(ROOT_PATH . '/public/js/test-mode.js')`, you can exclude `public/js/test-mode.js` from your production build/deployment artifact entirely.

When `TEST_MODE_AVAILABLE` is `false`:
- The **🧪 Test Mode** toggle button in the top navigation bar is not rendered in the HTML.
- The `/js/test-mode.js` script is not loaded.
- Zero test-mode code or UI is shipped to end users.

---

## 2. How to Use Test Mode on Application Edit & Create

1. **Navigate to an Application Form**:
   - **Edit Application**: `/applicants/{id}/edit`
   - **New Application**: `/applicants/create`
2. **Enable Test Mode**:
   - Click the **🧪 Test Mode: OFF** button either in the **top navigation bar** or directly inside the **form card header**.
   - The button indicator turns green (`🧪 Test Mode: ON`) and the **DEV TEST MODE Auto-Fill Toolbar** appears at the top of the form.
   - Your Test Mode state (`ON`/`OFF`) is saved in `localStorage` (`pp_test_mode_active`) so it persists as you navigate between pages.
3. **Select a Profile to Automatically Generate & Fill All Values**:
   - Open the **`-- Select Test Data Profile to Auto-Fill --`** dropdown:
     - **🎲 Random Realistic Applicant (Auto-Generate)**: Generates a brand-new, randomized Canadian/Alberta applicant profile every time.
     - **🌟 Profile 1: High-Income Executive (Fixed 1-Yr)**: Deterministic single executive profile ($8,750/mo, Fixed 12-month term).
     - **👨‍👩‍👧‍👦 Profile 2: Family Household (2 Adults, 2 Children)**: Deterministic family profile ($7,400/mo, 2 adults, 2 children, Fixed 12-month term).
     - **🔄 Profile 3: Skilled Trades Renter (Periodic Month-to-Month)**: Deterministic periodic tenancy profile ($6,200/mo, month-to-month).
     - **🎓 Profile 4: Recent Graduate / First-Time Renter**: Deterministic entry-level profile ($4,650/mo, roommate + guarantor contact).
   - **Selecting any option immediately populates all 38+ fields**, including:
     - Automatically selecting a valid **Property** and filtering/selecting a matching **Unit**
     - Calculating the **Term Beginning** and **Term Ending** dates
     - Formatting all 10-digit **Phone Numbers** (`XXX-XXX-XXXX`)
     - Auto-expanding multi-line **Adult Occupants** textareas
     - Filling **Personal Info**, **Current & Previous Employment**, **Monthly Income**, **Current & Previous Landlords**, **Emergency Contacts #1 & #2**, **Credit Check Authorization**, **Application Date**, **Applicant Signature**, and **Internal Notes**
4. **Additional Toolbar Buttons**:
   - **⚡ Generate & Fill All**: Re-applies the currently selected profile.
   - **🎲 New Random**: Generates a fresh randomized applicant dataset with one click.
   - **🧹 Clear**: Clears all form fields back to empty.

---

## 3. Built-In Support for Other Views

Out of the box, [`public/js/test-mode.js`](../public/js/test-mode.js) includes modules for:
- **Rental Applications** (`/applicants/create`, `/applicants/{id}/edit`)
- **Properties** (`/properties/create`, `/properties/{id}/edit`)
- **Units** (`/properties/{id}/units/create`, `/units/{id}/edit`)
- **Tenants** (`/tenants/create`, `/tenants/{id}/edit`)
- **Maintenance Tickets** (`/maintenance/create`)

---

## 4. Expanding Test Mode to New Views

The Test Mode engine exposes a global JavaScript registry (`window.PropPulseTestMode`) in [`public/js/test-mode.js`](../public/js/test-mode.js).

To add Test Mode auto-fill support to any new view or form, call `PropPulseTestMode.registerModule(moduleId, config)`:

```javascript
window.PropPulseTestMode.registerModule('leases', {
    // 1. Human-readable title shown in the Test Mode banner
    label: 'Lease Agreement Form',

    // 2. Detection function: return true when the current page DOM matches this view
    match: () => Boolean(
        document.getElementById('unit_id') &&
        document.getElementById('rent_amount') &&
        document.getElementById('start_date')
    ),

    // 3. Optional selectors for where the header toggle and toolbar mount
    formSelector: '.card .card-body form',
    headerSelector: '.card .card-header',

    // 4. List of selectable presets / generators
    presets: [
        {
            id: 'standard_1yr_lease',
            label: '🎲 Standard 12-Month Residential Lease',
            generate: () => ({
                start_date: '2026-12-01',
                end_date: '2027-11-30',
                rent_amount: '1850.00',
                deposit_held: '1850.00',
                status: 'active'
            })
        }
    ],

    // 5. Optional customApply(data, presetId) hook for cascading selects or custom logic
    // If omitted, PropPulseTestMode automatically maps each key in `data` to the input ID/name.
});
```

### Programmatic Console Helpers
When debugging in the browser DevTools console, you can also call:
- `PropPulseTestMode.enable()` — Turn Test Mode ON
- `PropPulseTestMode.disable()` — Turn Test Mode OFF
- `PropPulseTestMode.fillCurrentForm('random')` — Trigger auto-fill on the active form
