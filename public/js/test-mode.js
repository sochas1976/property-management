/**
 * PropPulse PMS — Expandable Development Test Mode & Auto-Fill Engine
 * ============================================================================
 * WARNING: Development & Testing Tool Only.
 * Guarded by `TEST_MODE_AVAILABLE` in `config/app.php` (`APP_ENV !== 'production'`).
 * Never loaded in production environments.
 *
 * Expandable Architecture:
 * Use `window.PropPulseTestMode.registerModule(id, moduleDefinition)` to add
 * test data generators and presets for any view/form in the application.
 */

(function () {
    'use strict';

    const STORAGE_KEY = 'pp_test_mode_active';
    const modules = {};
    let activeModule = null;

    // =========================================================================
    // 1. Random Data Helpers & Realistic Pools
    // =========================================================================
    const pick = (arr) => arr[Math.floor(Math.random() * arr.length)];
    const randInt = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;

    const FIRST_NAMES = ['Alexander', 'Victoria', 'Marcus', 'Natalie', 'Julian', 'Hannah', 'Gabriel', 'Samantha', 'Benjamin', 'Olivia', 'Nathaniel', 'Evelyn', 'Sebastian', 'Clara', 'Dominic', ' Maya'];
    const MIDDLE_NAMES = ['James', 'Marie', 'Alexander', 'Grace', 'Edward', 'Rose', 'Thomas', 'Claire', 'michael', 'Elizabeth', 'Arthur', 'Louise'];
    const LAST_NAMES = ['Vance', 'Kowalski', 'Thornton', 'Mercer', 'Rostova', 'MacLeod', 'Chen', 'O\'Connor', 'Lindqvist', 'Blackwood', 'Morrison', 'Tremblay', 'Delgado', 'Sinclair'];

    const STREETS = ['Jasper Ave NW', 'Whyte Ave NW', '104 Street NW', '124 Street NW', 'Saskatchewan Drive NW', ' Bellamy Hill Rd NW', '82 Avenue NW', '109 Street NW', 'Stony Plain Rd NW', 'Kingsway NW'];
    const CITIES = [
        { city: 'Edmonton', state: 'AB', postalPrefix: ['T5J', 'T5K', 'T6G', 'T6E', 'T5N', 'T6H'] },
        { city: 'St. Albert', state: 'AB', postalPrefix: ['T8N'] },
        { city: 'Sherwood Park', state: 'AB', postalPrefix: ['T8A', 'T8H'] },
        { city: 'Calgary', state: 'AB', postalPrefix: ['T2P', 'T2R', 'T3C'] }
    ];

    const EMPLOYERS = [
        { name: 'PCL Construction Management Inc.', occupation: 'Senior Project Coordinator', address: '9900 63 Ave NW, Edmonton, AB T6E 0G9' },
        { name: 'Alberta Health Services', occupation: 'Registered Clinical Nurse', address: '10030 107 St NW, Edmonton, AB T5J 3E4' },
        { name: 'Stantec Consulting Ltd.', occupation: 'Structural Engineer', address: '10220 103 Ave NW #400, Edmonton, AB T5J 0K4' },
        { name: 'ATB Financial Corporate', occupation: 'Senior Risk Analyst', address: '10020 100 St NW, Edmonton, AB T5J 0N3' },
        { name: 'Telus Communications Inc.', occupation: 'Network Systems Specialist', address: '10020 100 St NW #2200, Edmonton, AB T5J 0N5' },
        { name: 'University of Alberta', occupation: 'Research Associate', address: '116 St & 85 Ave, Edmonton, AB T6G 2R3' },
        { name: 'Epcor Utilities Inc.', occupation: 'Electrical Grid Technologist', address: '2000 10423 101 St NW, Edmonton, AB T5H 0E8' },
        { name: 'Enbridge Pipelines Inc.', occupation: 'Operations Compliance Lead', address: '10175 101 St NW, Edmonton, AB T5J 0H3' }
    ];

    const LANDLORDS = [
        'Boardwalk Rental Communities',
        'Mainstreet Equity Corp.',
        'Midwest Property Management',
        'Kelson Group Residential',
        'Ayre & Oxford Inc.',
        'Leston Holdings Ltd.',
        ' Broadstreet Properties'
    ];

    const RELATIONSHIPS = ['Parent', 'Sibling', 'Brother', 'Sister', 'Mother', 'Father', 'Close Friend', 'Uncle', 'Aunt'];

    function randomPhone() {
        const area = pick(['780', '587', '825']);
        const exch = randInt(210, 989);
        const line = randInt(1000, 9999);
        return `${area}-${exch}-${line}`;
    }

    function randomPostal(prefixList) {
        const prefix = pick(prefixList);
        const letters = 'ABCEGHJKLMNPRSTVWXYZ';
        const d1 = randInt(1, 9);
        const l1 = letters[randInt(0, letters.length - 1)];
        const d2 = randInt(1, 9);
        return `${prefix} ${d1}${l1}${d2}`;
    }

    function randomAddress() {
        const loc = pick(CITIES);
        const num = randInt(1010, 14950);
        const suite = Math.random() > 0.4 ? `Suite ${randInt(101, 1805)}, ` : '';
        const street = pick(STREETS).trim();
        const postal = randomPostal(loc.postalPrefix);
        return `${suite}${num} ${street}, ${loc.city}, ${loc.state} ${postal}`;
    }

    function formatDateYMD(dateObj) {
        const y = dateObj.getFullYear();
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const d = String(dateObj.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function getNextMonthFirstDate() {
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth() + 1, 1);
    }

    function calculateFixedEndDate(startDateObj) {
        const startYear = startDateObj.getFullYear();
        const startMonth = startDateObj.getMonth() + 1;
        const startDay = startDateObj.getDate();
        if (startDay === 1) {
            return new Date(startYear + 1, startMonth - 1, 0);
        }
        return new Date(startYear + 1, startMonth - 1, startDay - 1);
    }

    // =========================================================================
    // 2. DOM Field Population & Event Dispatching
    // =========================================================================
    function setFieldValue(selectorOrId, value) {
        if (value === undefined || value === null) return false;

        let el = document.getElementById(selectorOrId) || document.querySelector(`[name="${selectorOrId}"]`);
        if (!el) return false;

        if (el.type === 'checkbox') {
            el.checked = Boolean(value);
        } else {
            el.value = String(value);
        }

        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));

        // Visual feedback pulse
        el.classList.add('pp-test-filled-flash');
        setTimeout(() => el.classList.remove('pp-test-filled-flash'), 900);
        return true;
    }

    /**
     * Selects a valid Property & Unit pair on the Rental Application form
     */
    function selectPropertyAndUnit(preferredIndex) {
        const propSelect = document.getElementById('target_property_id');
        const unitSelect = document.getElementById('target_unit_id');
        if (!propSelect) return 0;

        let count = 0;
        const allPropOptions = Array.from(propSelect.querySelectorAll('option')).filter(opt => opt.value !== '');
        if (allPropOptions.length === 0) return 0;

        // Prefer properties that actually have units in the unit dropdown
        let propsWithUnits = allPropOptions;
        if (unitSelect) {
            const unitPropIds = new Set(
                Array.from(unitSelect.querySelectorAll('option'))
                    .map(opt => opt.getAttribute('data-property-id'))
                    .filter(Boolean)
            );
            const filtered = allPropOptions.filter(opt => unitPropIds.has(opt.value));
            if (filtered.length > 0) {
                propsWithUnits = filtered;
            }
        }

        const chosenProp = (typeof preferredIndex === 'number')
            ? propsWithUnits[preferredIndex % propsWithUnits.length]
            : pick(propsWithUnits);

        if (chosenProp) {
            propSelect.value = chosenProp.value;
            propSelect.dispatchEvent(new Event('change', { bubbles: true }));
            if (typeof window.filterUnitsByProperty === 'function') {
                window.filterUnitsByProperty(chosenProp.value);
            }
            count++;

            if (unitSelect) {
                const matchingUnits = Array.from(unitSelect.querySelectorAll('option')).filter(
                    opt => opt.value !== '' && opt.getAttribute('data-property-id') === chosenProp.value
                );
                if (matchingUnits.length > 0) {
                    const chosenUnit = (typeof preferredIndex === 'number')
                        ? matchingUnits[preferredIndex % matchingUnits.length]
                        : pick(matchingUnits);
                    unitSelect.value = chosenUnit.value;
                    unitSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    count++;
                }
            }
        }
        return count;
    }

    // =========================================================================
    // 3. Rental Application Data Generators & Presets
    // =========================================================================
    function generateRandomApplicantData() {
        const firstName = pick(FIRST_NAMES).trim();
        const middleName = pick(MIDDLE_NAMES).trim();
        const lastName = pick(LAST_NAMES).trim();
        const fullName = `${firstName} ${middleName} ${lastName}`;

        const hasCoApplicant = Math.random() > 0.35;
        const coFirstName = pick(FIRST_NAMES.filter(n => n.trim() !== firstName)).trim();
        const coLastName = Math.random() > 0.4 ? lastName : pick(LAST_NAMES).trim();

        const hasChildren = Math.random() > 0.5;
        const childOptions = [
            'Liam (7), Emma (4)',
            'Noah (10)',
            'Olivia (8), Ava (5), Lucas (2)',
            'Ethan (12), Mia (9)'
        ];

        const curJob = pick(EMPLOYERS);
        const prevJob = pick(EMPLOYERS.filter(e => e.name !== curJob.name));

        const curLandlord = pick(LANDLORDS).trim();
        const prevLandlord = pick(LANDLORDS.filter(l => l.trim() !== curLandlord)).trim();

        const startDate = getNextMonthFirstDate();
        const endDate = calculateFixedEndDate(startDate);
        const tenancyType = Math.random() > 0.2 ? 'fixed' : 'periodic';

        const birthYear = randInt(1975, 2001);
        const birthMonth = String(randInt(1, 12)).padStart(2, '0');
        const birthDay = String(randInt(1, 28)).padStart(2, '0');

        const emailSlug = `${firstName.toLowerCase()}.${lastName.toLowerCase().replace(/[^a-z]/g, '')}${randInt(10, 99)}`;

        return {
            status: 'pending',
            date_requested: formatDateYMD(new Date()),
            occupants_children: hasChildren ? pick(childOptions) : 'None',
            occupants_adults: hasCoApplicant
                ? `${firstName} ${lastName} (Primary Applicant)\n${coFirstName} ${coLastName} (Co-Applicant / Spouse)`
                : `${firstName} ${lastName} (Primary Applicant)`,
            tenancy_type: tenancyType,
            term_start_date: formatDateYMD(startDate),
            term_end_date: tenancyType === 'fixed' ? formatDateYMD(endDate) : '',
            first_name: firstName,
            middle_name: middleName,
            last_name: lastName,
            birthday: `${birthYear}-${birthMonth}-${birthDay}`,
            driver_license: `${randInt(100000, 999999)}-${randInt(100, 999)}`,
            cell_phone: randomPhone(),
            home_phone: randomPhone(),
            email: `${emailSlug}@example.com`,
            present_address: randomAddress(),
            present_address_duration: `${randInt(1, 4)} years ${randInt(1, 11)} months`,
            previous_address: randomAddress(),
            previous_address_duration: `${randInt(1, 3)} years ${randInt(2, 10)} months`,
            current_employer: curJob.name,
            current_occupation: curJob.occupation,
            current_employment_duration: `${randInt(2, 6)} years ${randInt(1, 11)} months`,
            current_supervisor: `${pick(FIRST_NAMES).trim()} ${pick(LAST_NAMES).trim()}`,
            current_employer_phone: randomPhone(),
            current_employer_address: curJob.address,
            previous_employer: prevJob.name,
            previous_occupation: prevJob.occupation,
            previous_employment_duration: `${randInt(1, 3)} years ${randInt(2, 10)} months`,
            previous_supervisor: `${pick(FIRST_NAMES).trim()} ${pick(LAST_NAMES).trim()}`,
            previous_employer_phone: randomPhone(),
            previous_employer_address: prevJob.address,
            monthly_income: (randInt(42, 98) * 100).toFixed(2),
            current_landlord_name: curLandlord,
            current_landlord_phone: randomPhone(),
            previous_landlord_name: prevLandlord,
            previous_landlord_phone: randomPhone(),
            emergency_contact_1_name: `${pick(FIRST_NAMES).trim()} ${lastName}`,
            emergency_contact_1_relationship: pick(RELATIONSHIPS),
            emergency_contact_1_phone: randomPhone(),
            emergency_contact_1_address: randomAddress(),
            emergency_contact_2_name: `${pick(FIRST_NAMES).trim()} ${pick(LAST_NAMES).trim()}`,
            emergency_contact_2_relationship: pick(RELATIONSHIPS),
            emergency_contact_2_phone: randomPhone(),
            emergency_contact_2_address: randomAddress(),
            credit_check_authorized: true,
            application_date: formatDateYMD(new Date()),
            applicant_signature: fullName,
            notes: `[TEST DATA] Auto-generated realistic application profile for ${fullName}. Verified employment at ${curJob.name}, strong landlord references, and clean credit authorization.`
        };
    }

    function getPresetApplicantData(presetKey) {
        const startDate = getNextMonthFirstDate();
        const endDate = calculateFixedEndDate(startDate);
        const today = formatDateYMD(new Date());

        const presets = {
            executive_fixed: {
                status: 'pending',
                date_requested: today,
                occupants_children: 'None',
                occupants_adults: 'Elena Marie Rostova (Primary Applicant)',
                tenancy_type: 'fixed',
                term_start_date: formatDateYMD(startDate),
                term_end_date: formatDateYMD(endDate),
                first_name: 'Elena',
                middle_name: 'Marie',
                last_name: 'Rostova',
                birthday: '1988-04-16',
                driver_license: '849201-442',
                cell_phone: '780-492-8810',
                home_phone: '780-439-1204',
                email: 'elena.rostova@example.com',
                present_address: 'Suite 1402, 10180 104 Street NW, Edmonton, AB T5J 1A7',
                present_address_duration: '3 years 8 months',
                previous_address: 'Suite 604, 9925 Jasper Ave NW, Edmonton, AB T5J 2X4',
                previous_address_duration: '2 years 4 months',
                current_employer: 'Stantec Consulting Ltd.',
                current_occupation: 'Principal Systems Architect',
                current_employment_duration: '4 years 6 months',
                current_supervisor: 'Jonathan R. Mercer',
                current_employer_phone: '780-917-7000',
                current_employer_address: '10220 103 Ave NW #400, Edmonton, AB T5J 0K4',
                previous_employer: 'ATB Financial Corporate',
                previous_occupation: 'Lead Solutions Engineer',
                previous_employment_duration: '3 years 2 months',
                previous_supervisor: 'Catherine L. Vance',
                previous_employer_phone: '780-408-6500',
                previous_employer_address: '10020 100 St NW, Edmonton, AB T5J 0N3',
                monthly_income: '8750.00',
                current_landlord_name: 'Boardwalk Rental Communities (Icon Towers)',
                current_landlord_phone: '780-429-1455',
                previous_landlord_name: 'Midwest Property Management',
                previous_landlord_phone: '780-451-8890',
                emergency_contact_1_name: 'Viktor Rostov',
                emergency_contact_1_relationship: 'Father',
                emergency_contact_1_phone: '780-466-3192',
                emergency_contact_1_address: '4210 124 Street NW, Edmonton, AB T6J 2B1',
                emergency_contact_2_name: 'Nadia Kowalski',
                emergency_contact_2_relationship: 'Sister',
                emergency_contact_2_phone: '587-338-9021',
                emergency_contact_2_address: '8812 Saskatchewan Drive NW, Edmonton, AB T6G 2B2',
                credit_check_authorized: true,
                application_date: today,
                applicant_signature: 'Elena Marie Rostova',
                notes: '[TEST PRESET: Executive Fixed] Excellent income-to-rent ratio ($8,750/mo). 4.5 years tenure at Stantec Consulting. Ready for immediate approval.'
            },
            family_household: {
                status: 'pending',
                date_requested: today,
                occupants_children: 'Lucas Thornton (9), Maya Thornton (6)',
                occupants_adults: 'David James Thornton (Primary Applicant)\nClaire Elizabeth Thornton (Spouse / Co-Applicant)',
                tenancy_type: 'fixed',
                term_start_date: formatDateYMD(startDate),
                term_end_date: formatDateYMD(endDate),
                first_name: 'David',
                middle_name: 'James',
                last_name: 'Thornton',
                birthday: '1985-09-22',
                driver_license: '632198-775',
                cell_phone: '780-619-4432',
                home_phone: '780-482-7761',
                email: 'david.thornton.family@example.com',
                present_address: '11420 76 Avenue NW, Edmonton, AB T6G 0K4',
                present_address_duration: '4 years 0 months',
                previous_address: '308 109 Street NW, Edmonton, AB T6G 1E5',
                previous_address_duration: '2 years 6 months',
                current_employer: 'PCL Construction Management Inc.',
                current_occupation: 'Senior Site Superintendent',
                current_employment_duration: '5 years 3 months',
                current_supervisor: 'Robert G. MacLeod',
                current_employer_phone: '780-733-5000',
                current_employer_address: '9900 63 Ave NW, Edmonton, AB T6E 0G9',
                previous_employer: 'Graham Construction & Engineering',
                previous_occupation: 'Project Coordinator',
                previous_employment_duration: '2 years 10 months',
                previous_supervisor: 'Steven A. Lindqvist',
                previous_employer_phone: '780-465-7200',
                previous_employer_address: '6215 86 Ave SE, Calgary, AB T2C 2S4',
                monthly_income: '7400.00',
                current_landlord_name: 'Kelson Group Residential',
                current_landlord_phone: '780-433-8911',
                previous_landlord_name: 'Ayre & Oxford Inc.',
                previous_landlord_phone: '780-448-4984',
                emergency_contact_1_name: 'Margaret Thornton',
                emergency_contact_1_relationship: 'Mother',
                emergency_contact_1_phone: '780-459-2180',
                emergency_contact_1_address: '18 Oakridge Drive, St. Albert, AB T8N 3M2',
                emergency_contact_2_name: 'Thomas Sinclair',
                emergency_contact_2_relationship: 'Brother-in-Law',
                emergency_contact_2_phone: '587-521-6640',
                emergency_contact_2_address: '214 Baseline Rd, Sherwood Park, AB T8A 5V1',
                credit_check_authorized: true,
                application_date: today,
                applicant_signature: 'David James Thornton',
                notes: '[TEST PRESET: Family Household] Married couple with two school-age children. Requesting long-term fixed tenancy near neighborhood schools.'
            },
            periodic_trades: {
                status: 'pending',
                date_requested: today,
                occupants_children: 'None',
                occupants_adults: 'Liam Edward O\'Connor (Primary Applicant)',
                tenancy_type: 'periodic',
                term_start_date: formatDateYMD(startDate),
                term_end_date: '',
                first_name: 'Liam',
                middle_name: 'Edward',
                last_name: 'O\'Connor',
                birthday: '1991-11-05',
                driver_license: '910443-218',
                cell_phone: '587-920-3318',
                home_phone: '780-471-0922',
                email: 'liam.oconnor91@example.com',
                present_address: 'Suite 305, 10615 Whyte Ave NW, Edmonton, AB T6E 2A7',
                present_address_duration: '2 years 2 months',
                previous_address: 'Suite 108, 10820 82 Avenue NW, Edmonton, AB T6E 2B3',
                previous_address_duration: '1 year 9 months',
                current_employer: 'Epcor Utilities Inc.',
                current_occupation: 'Master Journeyman Electrician',
                current_employment_duration: '3 years 11 months',
                current_supervisor: 'Daniel K. Tremblay',
                current_employer_phone: '780-412-3000',
                current_employer_address: '2000 10423 101 St NW, Edmonton, AB T5H 0E8',
                previous_employer: 'Chemco Electrical Contractors Ltd.',
                previous_occupation: 'Commercial Electrician',
                previous_employment_duration: '2 years 5 months',
                previous_supervisor: 'Greg P. Morrison',
                previous_employer_phone: '780-436-9570',
                previous_employer_address: '9220 39 Ave NW, Edmonton, AB T6E 5T9',
                monthly_income: '6200.00',
                current_landlord_name: 'Leston Holdings Ltd.',
                current_landlord_phone: '780-425-1155',
                previous_landlord_name: 'Mainstreet Equity Corp.',
                previous_landlord_phone: '780-423-3399',
                emergency_contact_1_name: 'Patrick O\'Connor',
                emergency_contact_1_relationship: 'Father',
                emergency_contact_1_phone: '780-477-8129',
                emergency_contact_1_address: '13210 95 Street NW, Edmonton, AB T5E 3Y4',
                emergency_contact_2_name: 'Sean O\'Connor',
                emergency_contact_2_relationship: 'Brother',
                emergency_contact_2_phone: '587-882-1940',
                emergency_contact_2_address: '7810 112 Avenue NW, Edmonton, AB T5B 0E8',
                credit_check_authorized: true,
                application_date: today,
                applicant_signature: 'Liam Edward O\'Connor',
                notes: '[TEST PRESET: Periodic Renter] Requesting month-to-month periodic tenancy. Full-time Epcor employee.'
            },
            first_time_grad: {
                status: 'pending',
                date_requested: today,
                occupants_children: 'None',
                occupants_adults: 'Sophia Louise Chen (Primary Applicant)\nHannah Grace Lindqvist (Roommate)',
                tenancy_type: 'fixed',
                term_start_date: formatDateYMD(startDate),
                term_end_date: formatDateYMD(endDate),
                first_name: 'Sophia',
                middle_name: 'Louise',
                last_name: 'Chen',
                birthday: '1999-06-14',
                driver_license: '551920-883',
                cell_phone: '825-419-7720',
                home_phone: '780-432-6610',
                email: 'sophia.chen.acct@example.com',
                present_address: '8904 112 Street NW (Student Residence), Edmonton, AB T6G 2C5',
                present_address_duration: '1 year 8 months',
                previous_address: '5420 Riverbend Rd NW, Edmonton, AB T6H 5K2',
                previous_address_duration: '4 years 0 months',
                current_employer: 'MNP LLP Chartered Professional Accountants',
                current_occupation: 'Staff Accountant (CPA Candidate)',
                current_employment_duration: '1 year 2 months',
                current_supervisor: 'Evelyn S. Blackwood',
                current_employer_phone: '780-451-4406',
                current_employer_address: '10235 101 St NW #1600, Edmonton, AB T5J 3G1',
                previous_employer: 'University of Alberta Faculty of Business',
                previous_occupation: 'Teaching & Research Assistant',
                previous_employment_duration: '1 year 6 months',
                previous_supervisor: 'Dr. Arthur Delgado',
                previous_employer_phone: '780-492-3901',
                previous_employer_address: '116 St & 85 Ave, Edmonton, AB T6G 2R3',
                monthly_income: '4650.00',
                current_landlord_name: 'University Campus Residence Services',
                current_landlord_phone: '780-492-3345',
                previous_landlord_name: 'Parental Residence (Richard & Mei Chen)',
                previous_landlord_phone: '780-430-9981',
                emergency_contact_1_name: 'Richard Chen',
                emergency_contact_1_relationship: 'Father (Guarantor)',
                emergency_contact_1_phone: '780-430-9981',
                emergency_contact_1_address: '5420 Riverbend Rd NW, Edmonton, AB T6H 5K2',
                emergency_contact_2_name: 'Mei Chen',
                emergency_contact_2_relationship: 'Mother',
                emergency_contact_2_phone: '780-905-4412',
                emergency_contact_2_address: '5420 Riverbend Rd NW, Edmonton, AB T6H 5K2',
                credit_check_authorized: true,
                application_date: today,
                applicant_signature: 'Sophia Louise Chen',
                notes: '[TEST PRESET: First-Time Renter] Recent U of A graduate now full-time at MNP LLP. Parents available as lease guarantors if required.'
            }
        };

        return presets[presetKey] || generateRandomApplicantData();
    }

    // =========================================================================
    // 4. Register Built-In Modules (Expandable View Registry)
    // =========================================================================

    /**
     * Public API to register new view modules for Test Mode
     */
    function registerModule(id, definition) {
        modules[id] = Object.assign({
            id: id,
            label: id,
            match: () => false,
            formSelector: '.card .card-body form, form',
            headerSelector: '.card .card-header, .page-header',
            presets: [],
            customApply: null
        }, definition);
    }

    // --- MODULE 1: Rental Applications (Create & Edit) ---
    registerModule('applicants', {
        label: 'Rental Application Form',
        match: () => Boolean(document.getElementById('first_name') && document.getElementById('tenancy_type') && document.getElementById('occupants_adults')),
        presets: [
            {
                id: 'random',
                label: '-- Random Realistic Applicant --',
                generate: () => generateRandomApplicantData()
            },
            {
                id: 'executive_fixed',
                label: 'Profile 1: High-Income Executive (Fixed 1-Yr)',
                generate: () => getPresetApplicantData('executive_fixed')
            },
            {
                id: 'family_household',
                label: 'Profile 2: Family Household (2 Adults, 2 Children)',
                generate: () => getPresetApplicantData('family_household')
            },
            {
                id: 'periodic_trades',
                label: 'Profile 3: Skilled Trades Renter (Periodic Month-to-Month)',
                generate: () => getPresetApplicantData('periodic_trades')
            },
            {
                id: 'first_time_grad',
                label: 'Profile 4: Recent Graduate / First-Time Renter',
                generate: () => getPresetApplicantData('first_time_grad')
            }
        ],
        customApply: (data, presetId) => {
            let filledCount = 0;

            // 1. Select Property & Unit dynamically
            const propIdx = presetId === 'executive_fixed' ? 0
                : presetId === 'family_household' ? 1
                : presetId === 'periodic_trades' ? 2
                : undefined;
            filledCount += selectPropertyAndUnit(propIdx);

            // 2. Apply Tenancy Type first so toggleTenancyFields runs before term_end_date
            if (data.tenancy_type) {
                if (setFieldValue('tenancy_type', data.tenancy_type)) filledCount++;
                if (typeof window.toggleTenancyFields === 'function') {
                    window.toggleTenancyFields(data.tenancy_type);
                }
            }

            // 3. Apply all remaining fields
            Object.keys(data).forEach(key => {
                if (key === 'tenancy_type') return;
                if (setFieldValue(key, data[key])) {
                    filledCount++;
                }
            });

            // 4. Resize any auto-expanding textareas
            document.querySelectorAll('textarea.auto-expand').forEach(ta => {
                ta.style.height = 'auto';
                ta.style.height = ta.scrollHeight + 'px';
            });

            return {
                filledCount,
                summary: `${data.first_name} ${data.last_name} (${data.tenancy_type === 'fixed' ? 'Fixed Term' : 'Periodic'})`
            };
        }
    });

    // --- MODULE 2: Properties (Create & Edit) ---
    registerModule('properties', {
        label: 'Property Form',
        match: () => Boolean(document.getElementById('property_type') && document.getElementById('address_line1') && document.getElementById('zip_code')),
        presets: [
            {
                id: 'random_property',
                label: 'Random Residential Property',
                generate: () => {
                    const names = ['River Valley Terrace', 'Glenora Park Estates', 'Strathcona Heritage Lofts', 'Crestwood Garden Villas', 'Oliver Square Towers'];
                    const types = ['apartment', 'multi_family', 'single_family', 'condo'];
                    const loc = pick(CITIES);
                    return {
                        name: `${pick(names)} #${randInt(10, 99)}`,
                        property_type: pick(types),
                        address_line1: `${randInt(1010, 12900)} ${pick(STREETS).trim()}`,
                        address_line2: 'Main Management Office',
                        city: loc.city,
                        state: loc.state,
                        zip_code: randomPostal(loc.postalPrefix),
                        description: '[TEST DATA] Well-maintained residential complex featuring heated underground parking, secure keyless entry, and proximity to transit.'
                    };
                }
            }
        ],
        customApply: (data) => {
            let filledCount = 0;
            // Pick a manager if dropdown has options
            const mgrSelect = document.getElementById('manager_id');
            if (mgrSelect && mgrSelect.options.length > 1) {
                mgrSelect.selectedIndex = 1;
                mgrSelect.dispatchEvent(new Event('change', { bubbles: true }));
                filledCount++;
            }
            Object.keys(data).forEach(k => {
                if (setFieldValue(k, data[k])) filledCount++;
            });
            return { filledCount, summary: data.name };
        }
    });

    // --- MODULE 3: Units (Create & Edit) ---
    registerModule('units', {
        label: 'Unit Form',
        match: () => Boolean(document.getElementById('unit_number') && document.getElementById('target_rent') && document.getElementById('bedrooms')),
        presets: [
            {
                id: 'random_unit',
                label: 'Random Apartment / Suite Unit',
                generate: () => {
                    const floor = randInt(1, 8);
                    const unitNum = `${floor}0${randInt(1, 9)}`;
                    const beds = randInt(1, 3);
                    const rent = (1200 + beds * 350 + randInt(0, 3) * 50).toFixed(2);
                    return {
                        unit_number: unitNum,
                        floor: floor,
                        bedrooms: beds,
                        bathrooms: beds >= 2 ? '2.0' : '1.0',
                        square_feet: 600 + beds * 250 + randInt(0, 120),
                        target_rent: rent,
                        security_deposit: rent,
                        status: 'vacant'
                    };
                }
            }
        ],
        customApply: (data) => {
            let filledCount = 0;
            const unitEl = document.getElementById('unit_number');
            if (unitEl && unitEl.tagName === 'SELECT' && unitEl.options.length > 1) {
                unitEl.selectedIndex = randInt(1, unitEl.options.length - 1);
                unitEl.dispatchEvent(new Event('change', { bubbles: true }));
                filledCount++;
            } else if (setFieldValue('unit_number', data.unit_number)) {
                filledCount++;
            }
            Object.keys(data).forEach(k => {
                if (k === 'unit_number') return;
                if (setFieldValue(k, data[k])) filledCount++;
            });
            // Check appliances if present
            document.querySelectorAll('input[name="appliances[]"]').forEach(cb => {
                cb.checked = true;
                cb.dispatchEvent(new Event('change', { bubbles: true }));
            });
            return { filledCount, summary: `Unit ${unitEl ? unitEl.value : data.unit_number} ($${data.target_rent}/mo)` };
        }
    });

    // --- MODULE 4: Tenants (Create & Edit) ---
    registerModule('tenants', {
        label: 'Tenant Registration Form',
        match: () => Boolean(document.getElementById('first_name') && document.getElementById('emergency_contact') && !document.getElementById('tenancy_type')),
        presets: [
            {
                id: 'random_tenant',
                label: 'Random Tenant Profile',
                generate: () => {
                    const fn = pick(FIRST_NAMES).trim();
                    const ln = pick(LAST_NAMES).trim();
                    return {
                        first_name: fn,
                        last_name: ln,
                        email: `${fn.toLowerCase()}.${ln.toLowerCase().replace(/[^a-z]/g, '')}@example.com`,
                        phone: randomPhone(),
                        emergency_contact: `${pick(FIRST_NAMES).trim()} ${ln} (${pick(RELATIONSHIPS)})`,
                        emergency_phone: randomPhone(),
                        notes: `[TEST DATA] Tenant profile for ${fn} ${ln}. Parking Stall #${randInt(10, 85)}.`
                    };
                }
            }
        ]
    });

    // --- MODULE 5: Maintenance Tickets (Create) ---
    registerModule('maintenance', {
        label: 'Maintenance Ticket Form',
        match: () => Boolean(document.getElementById('category') && document.getElementById('priority') && document.getElementById('assigned_to')),
        presets: [
            {
                id: 'random_maintenance',
                label: 'Random Maintenance Ticket',
                generate: () => {
                    const tickets = [
                        { category: 'plumbing', priority: 'high', title: 'Kitchen under-sink P-trap slow leak', description: '[TEST DATA] Tenant reported water droplets accumulating under the kitchen sink cabinet when running the dishwasher.', assigned_to: 'Capital City Plumbing & Heating' },
                        { category: 'hvac', priority: 'medium', title: 'Thermostat not triggering furnace blower', description: '[TEST DATA] Zone thermostat clicks on but blower fan does not engage consistently. Filter replaced last month.', assigned_to: 'NorthStar HVAC Mechanical' },
                        { category: 'appliance', priority: 'low', title: 'Dishwasher upper rack roller wheel replacement', description: '[TEST DATA] Left roller wheel on the upper dishwasher rack is worn out and catching on the track.', assigned_to: 'AppliancePro Technicians' },
                        { category: 'electrical', priority: 'high', title: 'Bathroom GFCI outlet tripping intermittently', description: '[TEST DATA] Master bathroom GFCI receptacle trips when hair dryer is plugged in.', assigned_to: 'Prairie Line Electrical Ltd.' }
                    ];
                    return pick(tickets);
                }
            }
        ],
        customApply: (data) => {
            let filledCount = 0;
            const unitSel = document.getElementById('unit_id');
            if (unitSel && unitSel.options.length > 1) {
                unitSel.selectedIndex = randInt(1, unitSel.options.length - 1);
                unitSel.dispatchEvent(new Event('change', { bubbles: true }));
                filledCount++;
            }
            const tenantSel = document.getElementById('tenant_id');
            if (tenantSel && tenantSel.options.length > 1) {
                tenantSel.selectedIndex = randInt(1, tenantSel.options.length - 1);
                tenantSel.dispatchEvent(new Event('change', { bubbles: true }));
                filledCount++;
            }
            Object.keys(data).forEach(k => {
                if (setFieldValue(k, data[k])) filledCount++;
            });
            return { filledCount, summary: data.title };
        }
    });

    // --- MODULE 6: Leases (Create) ---
    registerModule('leases', {
        label: 'Lease Agreement Form',
        match: () => Boolean(document.getElementById('rent_amount') && document.getElementById('start_date') && document.getElementById('deposit_held') && document.getElementById('unit_id')),
        presets: [
            {
                id: 'standard_1yr',
                label: 'Standard 1-Year Fixed Lease',
                generate: () => {
                    const start = getNextMonthFirstDate();
                    const end = calculateFixedEndDate(start);
                    return {
                        start_date: formatDateYMD(start),
                        end_date: formatDateYMD(end),
                        rent_amount: '1850.00',
                        deposit_held: '1850.00'
                    };
                }
            },
            {
                id: 'immediate_lease',
                label: 'Immediate Move-In Lease',
                generate: () => {
                    const today = new Date();
                    const end = calculateFixedEndDate(today);
                    return {
                        start_date: formatDateYMD(today),
                        end_date: formatDateYMD(end),
                        rent_amount: '2200.00',
                        deposit_held: '2200.00'
                    };
                }
            }
        ],
        customApply: (data) => {
            let filledCount = 0;
            const unitSelect = document.getElementById('unit_id');
            if (unitSelect && unitSelect.options.length > 1) {
                const validOptions = Array.from(unitSelect.options).filter(o => o.value !== '');
                if (validOptions.length > 0) {
                    const chosen = pick(validOptions);
                    unitSelect.value = chosen.value;
                    unitSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    filledCount++;
                    const targetRent = chosen.getAttribute('data-rent');
                    const targetDep = chosen.getAttribute('data-deposit');
                    if (targetRent) data.rent_amount = parseFloat(targetRent).toFixed(2);
                    if (targetDep) data.deposit_held = parseFloat(targetDep).toFixed(2);
                }
            }
            const tenantSelect = document.getElementById('tenant_id');
            if (tenantSelect && tenantSelect.options.length > 1) {
                const validTenants = Array.from(tenantSelect.options).filter(o => o.value !== '');
                if (validTenants.length > 0) {
                    tenantSelect.value = pick(validTenants).value;
                    tenantSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    filledCount++;
                }
            }
            Object.keys(data).forEach(k => {
                if (setFieldValue(k, data[k])) filledCount++;
            });
            return { filledCount, summary: `Lease for Unit (${data.rent_amount}/mo)` };
        }
    });

    // --- MODULE 7: Applicant Conversion to Tenant ---
    registerModule('applicant_convert', {
        label: 'Convert Applicant to Tenant Form',
        match: () => Boolean(document.getElementById('modal_unit_id')),
        formSelector: 'form[action*="/convert"]',
        presets: [
            {
                id: 'convert_auto',
                label: 'Auto-Select Available Unit & Co-Applicants',
                generate: () => ({})
            }
        ],
        customApply: () => {
            const unitSel = document.getElementById('modal_unit_id');
            let filledCount = 0;
            if (unitSel && unitSel.options.length > 1) {
                if (!unitSel.value) {
                    unitSel.selectedIndex = 1;
                    unitSel.dispatchEvent(new Event('change', { bubbles: true }));
                    filledCount++;
                }
            }
            document.querySelectorAll('input[name="co_applicant_ids[]"]').forEach(cb => {
                cb.checked = true;
                filledCount++;
            });
            return { filledCount, summary: 'Unit & Co-Applicants Selected' };
        }
    });

    // =========================================================================
    // 5. UI Injection & Test Mode State Controller
    // =========================================================================
    function isTestModeEnabled() {
        try {
            return localStorage.getItem(STORAGE_KEY) === 'true';
        } catch (e) {
            return false;
        }
    }

    function setTestModeEnabled(enabled) {
        try {
            localStorage.setItem(STORAGE_KEY, enabled ? 'true' : 'false');
        } catch (e) {}
        syncTestModeUI();
    }

    function toggleTestMode(e) {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();
        setTestModeEnabled(!isTestModeEnabled());
    }

    function injectStyles() {
        if (document.getElementById('pp-test-mode-styles')) return;
        const style = document.createElement('style');
        style.id = 'pp-test-mode-styles';
        style.textContent = `
            .pp-test-mode-nav-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 5px 11px;
                border-radius: 999px;
                font-size: 12px;
                font-weight: 700;
                cursor: pointer;
                border: 1.5px dashed var(--ws-border);
                background: var(--ws-surface-subtle);
                color: var(--ws-text-muted);
                transition: all 0.2s ease;
                user-select: none;
            }
            .pp-test-mode-nav-btn:hover {
                border-color: #d97706;
                color: var(--ws-text);
            }
            .pp-test-mode-nav-btn.active {
                border-style: solid;
                border-color: #f59e0b;
                background: rgba(245, 158, 11, 0.14);
                color: #d97706;
                box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.12);
            }
            html[data-theme="dark"] .pp-test-mode-nav-btn.active {
                color: #fbbf24;
                border-color: #f59e0b;
                background: rgba(245, 158, 11, 0.18);
            }
            .pp-test-mode-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #9ca3af;
                transition: background 0.2s ease, box-shadow 0.2s ease;
            }
            .pp-test-mode-nav-btn.active .pp-test-mode-dot {
                background: #10b981;
                box-shadow: 0 0 6px #10b981;
            }
            .pp-test-toolbar {
                display: none;
                background: linear-gradient(135deg, rgba(245, 158, 11, 0.10) 0%, rgba(16, 185, 129, 0.08) 100%);
                border: 1.5px dashed #f59e0b;
                border-radius: 12px;
                padding: 14px 16px;
                margin-bottom: 22px;
                transition: all 0.2s ease;
            }
            .pp-test-toolbar.visible {
                display: block;
            }
            .pp-test-toolbar-top {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
                margin-bottom: 12px;
            }
            .pp-test-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 11.5px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.6px;
                color: #b45309;
                background: rgba(245, 158, 11, 0.18);
                padding: 3px 9px;
                border-radius: 6px;
                border: 1px solid rgba(245, 158, 11, 0.35);
            }
            html[data-theme="dark"] .pp-test-badge {
                color: #fbbf24;
                background: rgba(245, 158, 11, 0.22);
            }
            .pp-test-status {
                font-size: 12.5px;
                font-weight: 600;
                color: #059669;
                display: none;
                align-items: center;
                gap: 5px;
                padding-left: 2px;
            }
            html[data-theme="dark"] .pp-test-status {
                color: #34d399;
            }
            .pp-test-status.active {
                display: inline-flex;
            }
            .pp-test-controls {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
            }
            .pp-test-select {
                flex: 1;
                min-width: 250px;
                height: 38px;
                padding: 6px 12px;
                border-radius: 8px;
                border: 1px solid rgba(245, 158, 11, 0.24);
                background: rgba(245, 158, 11, 0.04);
                color: rgba(180, 83, 9, 0.72);
                font-size: 13.5px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.15s ease;
            }
            .pp-test-select option {
                color: #92400e;
                background: var(--card-bg);
            }
            .pp-test-select:focus, .pp-test-select:hover {
                border-color: #f59e0b;
                background: rgba(245, 158, 11, 0.12);
                color: #78350f;
                outline: none;
            }
            html[data-theme="dark"] .pp-test-select {
                background: rgba(245, 158, 11, 0.06);
                border-color: rgba(245, 158, 11, 0.22);
                color: rgba(253, 230, 138, 0.68);
            }
            html[data-theme="dark"] .pp-test-select option {
                color: #fde68a;
                background: #1f2937;
            }
            .pp-test-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                height: 38px;
                padding: 0 14px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                border: 1px solid rgba(245, 158, 11, 0.4);
                background: rgba(245, 158, 11, 0.10);
                color: #92400e;
                transition: all 0.15s ease;
                white-space: nowrap;
            }
            .pp-test-btn:hover {
                background: rgba(245, 158, 11, 0.20);
                border-color: #f59e0b;
                color: #78350f;
                transform: translateY(-1px);
            }
            html[data-theme="dark"] .pp-test-btn {
                background: rgba(245, 158, 11, 0.14);
                border-color: rgba(245, 158, 11, 0.35);
                color: #fde68a;
            }
            html[data-theme="dark"] .pp-test-btn:hover {
                background: rgba(245, 158, 11, 0.24);
                border-color: #f59e0b;
                color: #fef3c7;
            }
            .pp-test-btn-primary {
                background: rgba(245, 158, 11, 0.24);
                color: #78350f;
                border: 1.5px solid #f59e0b;
            }
            .pp-test-btn-primary:hover {
                background: rgba(245, 158, 11, 0.36);
                border-color: #d97706;
                color: #78350f;
            }
            html[data-theme="dark"] .pp-test-btn-primary {
                background: rgba(245, 158, 11, 0.28);
                color: #fef3c7;
                border: 1.5px solid #f59e0b;
            }
            html[data-theme="dark"] .pp-test-btn-primary:hover {
                background: rgba(245, 158, 11, 0.40);
                border-color: #fbbf24;
                color: #ffffff;
            }
            .pp-test-status {
                font-size: 12px;
                font-weight: 600;
                color: #059669;
                display: none;
                align-items: center;
                gap: 5px;
            }
            html[data-theme="dark"] .pp-test-status {
                color: #34d399;
            }
            .pp-test-status.active {
                display: inline-flex;
            }
            @keyframes ppFieldFlash {
                0% { box-shadow: 0 0 0 3px rgba(245, 166, 35, 0.38); border-color: var(--ws-accent, #f5a623); }
                100% { box-shadow: none; }
            }
            .pp-test-filled-flash {
                animation: ppFieldFlash 0.85s ease-out;
            }
        `;
        document.head.appendChild(style);
    }

    function applySelectedPreset(module, presetId) {
        if (!module || !presetId) return;
        const preset = module.presets.find(p => p.id === presetId) || module.presets[0];
        if (!preset) return;

        const data = preset.generate();
        let result = { filledCount: 0, summary: preset.label };

        if (typeof module.customApply === 'function') {
            result = module.customApply(data, preset.id);
        } else {
            let count = 0;
            Object.keys(data).forEach(key => {
                if (setFieldValue(key, data[key])) count++;
            });
            result = { filledCount: count, summary: preset.label };
        }

        const statusEl = document.getElementById('pp-test-status-msg');
        if (statusEl) {
            statusEl.textContent = `Filled ${result.filledCount} fields — ${result.summary}`;
            statusEl.classList.add('active');
        }
    }

    function clearFormFields(module) {
        const form = document.querySelector(module.formSelector);
        if (!form) return;

        form.querySelectorAll('input, textarea, select').forEach(el => {
            if (el.type === 'hidden' || el.type === 'submit' || el.type === 'button' || el.type === 'file') return;
            if (el.id && el.id.startsWith('pp-test-')) return;

            if (el.type === 'checkbox' || el.type === 'radio') {
                el.checked = false;
            } else if (el.tagName === 'SELECT') {
                if (el.id === 'status') return; // preserve status dropdown valid option
                el.selectedIndex = 0;
            } else {
                el.value = '';
            }
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });

        if (typeof window.filterUnitsByProperty === 'function') {
            window.filterUnitsByProperty('');
        }

        const esigClearBtn = document.getElementById('esig-clear-btn');
        if (esigClearBtn) {
            esigClearBtn.click();
        }

        const statusEl = document.getElementById('pp-test-status-msg');
        if (statusEl) {
            statusEl.textContent = 'Form Cleared';
            statusEl.classList.add('active');
        }
    }

    function mountModuleUI(module) {
        if (!module) return;
        const form = document.querySelector(module.formSelector);
        if (!form) return;

        // Ensure any old card-header test mode button is completely removed
        const oldHeaderBtn = document.getElementById('pp-card-header-test-btn');
        if (oldHeaderBtn) oldHeaderBtn.remove();

        // Inject the Test Mode Auto-Fill Toolbar directly above the form
        if (!document.getElementById('pp-test-mode-toolbar')) {
            const toolbar = document.createElement('div');
            toolbar.id = 'pp-test-mode-toolbar';
            toolbar.className = 'pp-test-toolbar';

            const optionsHtml = module.presets.map(p =>
                `<option value="${p.id}">${p.label}</option>`
            ).join('');

            const initialStatus = 'Form Cleared';

            toolbar.innerHTML = `
                <div class="pp-test-toolbar-top">
                    <span class="pp-test-badge">DEV TEST MODE — ${module.label}</span>
                    <span id="pp-test-status-msg" class="pp-test-status active">${initialStatus}</span>
                </div>
                <div class="pp-test-controls">
                    <select id="pp-test-preset-select" class="pp-test-select" aria-label="Select Test Data Profile">
                        ${optionsHtml}
                    </select>
                    <button type="button" id="pp-test-fill-btn" class="pp-test-btn pp-test-btn-primary" title="Generate and populate all form fields using the selected profile">
                        Generate and Fill All
                    </button>
                    <button type="button" id="pp-test-random-btn" class="pp-test-btn" title="Generate fresh random values and fill all fields immediately">
                        New Random
                    </button>
                    <button type="button" id="pp-test-clear-btn" class="pp-test-btn" title="Clear all form fields">
                        Clear
                    </button>
                </div>
            `;

            form.parentNode.insertBefore(toolbar, form);

            const selectEl = document.getElementById('pp-test-preset-select');
            const fillBtn = document.getElementById('pp-test-fill-btn');
            const randomBtn = document.getElementById('pp-test-random-btn');
            const clearBtn = document.getElementById('pp-test-clear-btn');

            // Auto-fill immediately when user selects an option from the dropdown
            selectEl.addEventListener('change', () => {
                if (selectEl.value) {
                    applySelectedPreset(module, selectEl.value);
                }
            });

            // "Generate & Fill All" button
            fillBtn.addEventListener('click', () => {
                if (!selectEl.value && module.presets.length > 0) {
                    selectEl.value = module.presets[0].id;
                }
                applySelectedPreset(module, selectEl.value);
            });

            // "New Random" button
            randomBtn.addEventListener('click', () => {
                if (module.presets.length > 0) {
                    selectEl.value = module.presets[0].id;
                    applySelectedPreset(module, module.presets[0].id);
                }
            });

            // "Clear" button
            clearBtn.addEventListener('click', () => {
                selectEl.value = '';
                clearFormFields(module);
            });
        }
    }

    function detectActiveModule() {
        for (const id of Object.keys(modules)) {
            if (modules[id].match()) {
                return modules[id];
            }
        }
        return null;
    }

    function handleNavbarBtnClick(e) {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();

        // Single button toggle between ON and OFF
        const willEnable = !isTestModeEnabled();
        setTestModeEnabled(willEnable);

        if (willEnable) {
            // Turning ON: detect view module and mount UI for this view's dataset
            activeModule = detectActiveModule();
            if (activeModule) {
                mountModuleUI(activeModule);
                const selectEl = document.getElementById('pp-test-preset-select');
                if (selectEl && !selectEl.value && activeModule.presets.length > 0) {
                    selectEl.value = activeModule.presets[0].id;
                }
            }
            // Set status under DEV TEST MODE badge
            const statusEl = document.getElementById('pp-test-status-msg');
            if (statusEl) {
                statusEl.textContent = 'Form Cleared';
                statusEl.classList.add('active');
            }
        }
    }

    function syncTestModeUI() {
        const enabled = isTestModeEnabled();

        // Update navbar button (single button toggling between ON and OFF)
        const navBtn = document.getElementById('navbar-test-mode-btn');
        if (navBtn) {
            navBtn.classList.toggle('active', enabled);
            const label = navBtn.querySelector('.pp-test-state-label');
            if (label) label.textContent = enabled ? 'ON' : 'OFF';

            // Ensure no extra icon is attached; keep it as a clean single button
            const offBtn = navBtn.querySelector('.pp-test-mode-turn-off');
            if (offBtn) offBtn.remove();

            navBtn.title = enabled
                ? 'Test Mode is ON — Click to toggle OFF'
                : 'Test Mode is OFF — Click to toggle ON and auto-fill dataset for this view';
        }

        // Show/hide toolbar
        const toolbar = document.getElementById('pp-test-mode-toolbar');
        if (toolbar) {
            toolbar.classList.toggle('visible', enabled);
            const statusEl = document.getElementById('pp-test-status-msg');
            if (statusEl) {
                if (!enabled) {
                    statusEl.classList.remove('active');
                } else if (!statusEl.textContent) {
                    statusEl.textContent = 'Form Cleared';
                    statusEl.classList.add('active');
                }
            }
        }
    }

    function init() {
        injectStyles();

        // Remove any old card header button if it was in the markup
        const oldHeaderBtn = document.getElementById('pp-card-header-test-btn');
        if (oldHeaderBtn) oldHeaderBtn.remove();

        // Bind navbar toggle button if present
        const navBtn = document.getElementById('navbar-test-mode-btn');
        if (navBtn) {
            navBtn.addEventListener('click', handleNavbarBtnClick);
        }

        // Detect which registered module matches the current view
        activeModule = detectActiveModule();
        if (activeModule) {
            mountModuleUI(activeModule);
            const selectEl = document.getElementById('pp-test-preset-select');
            if (selectEl && !selectEl.value && activeModule.presets.length > 0) {
                selectEl.value = activeModule.presets[0].id;
            }
        }

        syncTestModeUI();

        const statusEl = document.getElementById('pp-test-status-msg');
        if (statusEl && isTestModeEnabled()) {
            if (!statusEl.textContent) {
                statusEl.textContent = 'Form Cleared';
                statusEl.classList.add('active');
            }
        }
    }

    // Expose global API for expandability across custom views
    window.PropPulseTestMode = {
        registerModule: registerModule,
        enable: () => setTestModeEnabled(true),
        disable: () => setTestModeEnabled(false),
        toggle: toggleTestMode,
        isEnabled: isTestModeEnabled,
        fillCurrentForm: (presetId) => {
            if (activeModule) {
                setTestModeEnabled(true);
                applySelectedPreset(activeModule, presetId || activeModule.presets[0]?.id);
            }
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
