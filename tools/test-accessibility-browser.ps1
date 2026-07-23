param(
    [string]$BaseUrl = "http://lakeuden-kauppaseura.local"
)

$ErrorActionPreference = "Stop"
$edge = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"

if (-not (Test-Path -LiteralPath $edge -PathType Leaf)) {
    throw "Microsoft Edge was not found at $edge."
}

$port = Get-Random -Minimum 9300 -Maximum 9900
$profile = Join-Path ([IO.Path]::GetTempPath()) ("lks-a11y-" + [Guid]::NewGuid().ToString("N"))
$profile = [IO.Path]::GetFullPath($profile)
$tempRoot = [IO.Path]::GetFullPath([IO.Path]::GetTempPath())
New-Item -ItemType Directory -Path $profile | Out-Null

$edgeProcess = $null
$socket = $null
$failures = [Collections.Generic.List[string]]::new()

function Add-Check {
    param(
        [bool]$Condition,
        [string]$Message
    )

    if ($Condition) {
        Write-Host "PASS  $Message"
    } else {
        Write-Host "FAIL  $Message"
        $script:failures.Add($Message)
    }
}

try {
    $edgeProcess = Start-Process -FilePath $edge -WindowStyle Hidden -PassThru -ArgumentList @(
        "--headless=new",
        "--disable-gpu",
        "--remote-debugging-port=$port",
        "--user-data-dir=$profile",
        "--window-size=390,844",
        "--no-first-run",
        "--disable-default-apps",
        "$($BaseUrl.TrimEnd('/'))/jaseneksi/"
    )

    $deadline = (Get-Date).AddSeconds(15)
    do {
        Start-Sleep -Milliseconds 250
        try {
            $targets = Invoke-RestMethod -Uri "http://127.0.0.1:$port/json/list" -TimeoutSec 2
        } catch {
            $targets = $null
        }
    } until ($targets -or (Get-Date) -gt $deadline)

    if (-not $targets) {
        throw "Edge DevTools endpoint did not start."
    }

    $target = $targets | Where-Object { $_.type -eq "page" } | Select-Object -First 1
    $socket = [Net.WebSockets.ClientWebSocket]::new()
    $socket.ConnectAsync([Uri]$target.webSocketDebuggerUrl, [Threading.CancellationToken]::None).Wait()
    $script:cdpId = 0

    function Send-Cdp {
        param(
            [string]$Method,
            [hashtable]$Params = @{}
        )

        $script:cdpId++
        $id = $script:cdpId
        $json = @{
            id = $id
            method = $Method
            params = $Params
        } | ConvertTo-Json -Compress -Depth 20
        $bytes = [Text.Encoding]::UTF8.GetBytes($json)
        $socket.SendAsync(
            [ArraySegment[byte]]::new($bytes),
            [Net.WebSockets.WebSocketMessageType]::Text,
            $true,
            [Threading.CancellationToken]::None
        ).Wait()

        while ($true) {
            $builder = [Text.StringBuilder]::new()
            do {
                $buffer = New-Object byte[] 65536
                $receive = $socket.ReceiveAsync(
                    [ArraySegment[byte]]::new($buffer),
                    [Threading.CancellationToken]::None
                ).Result
                [void]$builder.Append([Text.Encoding]::UTF8.GetString($buffer, 0, $receive.Count))
            } until ($receive.EndOfMessage)

            $message = $builder.ToString() | ConvertFrom-Json
            if ($message.id -eq $id) {
                return $message
            }
        }
    }

    function Get-BrowserValue {
        param([string]$Expression)

        $message = Send-Cdp "Runtime.evaluate" @{
            expression = "JSON.stringify(($Expression))"
            returnByValue = $true
            awaitPromise = $true
        }

        if ($message.error -or $message.result.exceptionDetails) {
            throw ($message | ConvertTo-Json -Depth 10)
        }

        $value = $message.result.result.value
        if ($null -eq $value -or "undefined" -eq $value) {
            return $null
        }

        return $value | ConvertFrom-Json
    }

    function Send-Key {
        param(
            [string]$Key,
            [string]$Code,
            [int]$VirtualKey,
            [int]$Modifiers = 0
        )

        [void](Send-Cdp "Input.dispatchKeyEvent" @{
            type = "keyDown"
            key = $Key
            code = $Code
            windowsVirtualKeyCode = $VirtualKey
            nativeVirtualKeyCode = $VirtualKey
            modifiers = $Modifiers
        })
        [void](Send-Cdp "Input.dispatchKeyEvent" @{
            type = "keyUp"
            key = $Key
            code = $Code
            windowsVirtualKeyCode = $VirtualKey
            nativeVirtualKeyCode = $VirtualKey
            modifiers = $Modifiers
        })
    }

    [void](Send-Cdp "Runtime.enable")
    [void](Send-Cdp "Page.enable")
    [void](Send-Cdp "Emulation.setDeviceMetricsOverride" @{
        width = 390
        height = 844
        deviceScaleFactor = 1
        mobile = $true
    })
    Start-Sleep -Seconds 2

    $initial = Get-BrowserValue @'
({
    ready: document.readyState,
    menuVisible: getComputedStyle(document.querySelector(".lks-mobile-menu")).display,
    expanded: document.querySelector(".lks-mobile-menu summary").getAttribute("aria-expanded"),
    formLabels: [...document.querySelectorAll(".lks-membership-form-live input:not([type=hidden]), .lks-membership-form-live textarea")]
        .filter((element) => getComputedStyle(element).visibility !== "hidden")
        .every((element) => Boolean(document.querySelector(`label[for="${element.id}"]`)) || Boolean(element.closest("fieldset")?.querySelector("legend"))),
    spinnerAlt: document.querySelector(".wpforms-submit-spinner")?.getAttribute("alt"),
    spinnerHidden: document.querySelector(".wpforms-submit-spinner")?.getAttribute("aria-hidden"),
    triggerSize: (() => {
        const rect = document.querySelector(".lks-mobile-menu summary").getBoundingClientRect();
        return {width: rect.width, height: rect.height};
    })(),
    submitHeight: document.querySelector(".wpforms-submit").getBoundingClientRect().height,
    choiceLabelHeight: document.querySelector(".wpforms-field-label-inline").getBoundingClientRect().height,
    privacyAcknowledgement: {
        label: document.querySelector('[name="wpforms[fields][9][]"]')?.closest(".wpforms-field")?.querySelector(".wpforms-field-label")?.textContent.trim(),
        required: document.querySelector('[name="wpforms[fields][9][]"]')?.required
    },
    communicationsConsent: {
        label: document.querySelector('[name="wpforms[fields][10][]"]')?.closest(".wpforms-field")?.querySelector(".wpforms-field-label")?.textContent.trim(),
        required: document.querySelector('[name="wpforms[fields][10][]"]')?.required
    },
    localTestimonialPlaceholders: document.querySelectorAll('[data-lks-testimonial-placeholder="true"]').length
})
'@
    Add-Check ($initial.ready -eq "complete") "Membership page loads in the browser"
    Add-Check ($initial.menuVisible -eq "block") "Mobile menu trigger is visible at 390 px"
    Add-Check ($initial.expanded -eq "false") "Closed mobile menu exposes aria-expanded=false"
    Add-Check ([bool]$initial.formLabels) "Every visible form control has a label or fieldset legend"
    Add-Check ($initial.spinnerAlt -eq "" -and $initial.spinnerHidden -eq "true") "Decorative form spinner is hidden from assistive technology"
    Add-Check ($initial.triggerSize.width -ge 44 -and $initial.triggerSize.height -ge 44) "Mobile menu trigger is at least 44 by 44 CSS pixels"
    Add-Check ($initial.submitHeight -ge 44 -and $initial.choiceLabelHeight -ge 44) "Form submit and choice labels provide 44-pixel touch targets"
    Add-Check ($initial.privacyAcknowledgement.required -and $initial.privacyAcknowledgement.label -match "Tietosuojan") "Privacy acknowledgement is required and is not labelled as marketing consent"
    Add-Check (-not $initial.communicationsConsent.required -and $initial.communicationsConsent.label -match "Vapaaehtoinen") "Communications consent remains explicitly optional"
    Add-Check ($initial.localTestimonialPlaceholders -eq 3) "Local editing preview keeps the three clearly marked testimonial placeholders visible"

    $opened = Get-BrowserValue @'
(() => {
    const summary = document.querySelector(".lks-mobile-menu summary");
    summary.focus();
    summary.click();
    return {
        open: summary.parentElement.open,
        expanded: summary.getAttribute("aria-expanded"),
        mainInert: document.querySelector("#main").hasAttribute("inert")
    };
})()
'@
    Start-Sleep -Milliseconds 100
    $opened = Get-BrowserValue @'
({
    open: document.querySelector(".lks-mobile-menu").open,
    expanded: document.querySelector(".lks-mobile-menu summary").getAttribute("aria-expanded"),
    mainInert: document.querySelector("#main").hasAttribute("inert")
})
'@
    Add-Check ([bool]$opened.open -and $opened.expanded -eq "true") "Mobile menu opens and exposes aria-expanded=true"
    Add-Check ([bool]$opened.mainInert) "Background content becomes inert while the menu is open"

    Send-Key "Tab" "Tab" 9
    $afterTab = Get-BrowserValue '({tag: document.activeElement.tagName, text: document.activeElement.textContent.trim()})'
    Add-Check ($afterTab.tag -eq "A" -and $afterTab.text -eq "Etusivu") "Tab moves from the menu trigger to the first menu link"

    [void](Get-BrowserValue '(() => { document.querySelector(".lks-mobile-menu__contact").focus(); return true; })()')
    Send-Key "Tab" "Tab" 9
    $trapped = Get-BrowserValue '({tag: document.activeElement.tagName, text: document.activeElement.textContent.trim()})'
    Add-Check ($trapped.tag -eq "SUMMARY") "Tab is contained within the open mobile menu"

    Send-Key "Tab" "Tab" 9 8
    $reverseTrapped = Get-BrowserValue '({tag: document.activeElement.tagName, className: document.activeElement.className})'
    Add-Check ($reverseTrapped.tag -eq "A" -and $reverseTrapped.className -eq "lks-mobile-menu__contact") "Shift+Tab is contained within the open mobile menu"

    [void](Get-BrowserValue '(() => { document.querySelector(".lks-mobile-menu nav a").focus(); return true; })()')
    Send-Key "Escape" "Escape" 27
    $escaped = Get-BrowserValue @'
({
    open: document.querySelector(".lks-mobile-menu").open,
    expanded: document.querySelector(".lks-mobile-menu summary").getAttribute("aria-expanded"),
    mainInert: document.querySelector("#main").hasAttribute("inert"),
    activeTag: document.activeElement.tagName
})
'@
    Add-Check (-not $escaped.open -and $escaped.expanded -eq "false") "Escape closes the mobile menu"
    Add-Check (-not $escaped.mainInert -and $escaped.activeTag -eq "SUMMARY") "Escape restores background access and focus to the trigger"

    [void](Get-BrowserValue '(() => { document.querySelector(".lks-membership-form-live form").requestSubmit(); return true; })()')
    Start-Sleep -Milliseconds 800
    $missing = Get-BrowserValue @'
({
    invalid: [...document.querySelectorAll(".lks-membership-form-live [aria-invalid=true]")].map((element) => element.id),
    errors: [...document.querySelectorAll(".lks-membership-form-live .wpforms-error")]
        .filter((element) => element.textContent.trim())
        .map((element) => ({id: element.id, text: element.textContent.trim()})),
    associated: [...document.querySelectorAll(".lks-membership-form-live [aria-invalid=true]")]
        .every((element) => (element.getAttribute("aria-describedby") || "").split(/\s+/).some((id) => document.getElementById(id)))
})
'@
    Add-Check ($missing.invalid.Count -ge 1) "Missing required values set aria-invalid"
    Add-Check ($missing.errors.Count -ge 1) "Missing required values produce visible Finnish errors"
    Add-Check ([bool]$missing.associated) "Visible form errors are associated with invalid controls"

    [void](Get-BrowserValue @'
(() => {
    const form = document.querySelector(".lks-membership-form-live form");
    const values = {
        1: "Testikayttaja",
        2: "virheellinen-osoite",
        4: "Testiorganisaatio",
        5: "Testirooli",
        6: "Seinajoki",
        7: "Testaan lomakkeen virhetilaa."
    };
    for (const [fieldId, value] of Object.entries(values)) {
        const element = form.querySelector(`[name="wpforms[fields][${fieldId}]"]`);
        element.value = value;
        element.dispatchEvent(new Event("input", {bubbles: true}));
    }
    form.querySelector('[name="wpforms[fields][8]"]').checked = true;
    form.querySelector('[name="wpforms[fields][9][]"]').checked = true;
    form.requestSubmit();
    return true;
})()
'@)
    Start-Sleep -Milliseconds 800
    $emailError = Get-BrowserValue @'
(() => {
    const element = document.querySelector('[name="wpforms[fields][2]"]');
    const error = document.getElementById(element.getAttribute("aria-errormessage"));
    return {
        invalid: element.getAttribute("aria-invalid"),
        describedby: element.getAttribute("aria-describedby"),
        error: error?.textContent.trim() || ""
    };
})()
'@
    Add-Check ($emailError.invalid -eq "true") "Invalid email sets aria-invalid"
    Add-Check ($emailError.describedby -and $emailError.error.StartsWith("Anna kelvollinen")) "Invalid email has an associated Finnish error"

    [void](Get-BrowserValue @'
(() => {
    const confirmation = document.createElement("div");
    confirmation.className = "wpforms-confirmation-container-full";
    confirmation.textContent = "Testivahvistus";
    document.querySelector(".lks-membership-form-section__form").append(confirmation);
    return true;
})()
'@)
    Start-Sleep -Milliseconds 200
    $success = Get-BrowserValue @'
(() => {
    const confirmation = document.querySelector(".wpforms-confirmation-container-full");
    const result = {
        role: confirmation.getAttribute("role"),
        live: confirmation.getAttribute("aria-live"),
        focused: document.activeElement === confirmation
    };
    confirmation.remove();
    return result;
})()
'@
    Add-Check ($success.role -eq "status" -and $success.live -eq "polite" -and $success.focused) "Success message is announced and focused"

    [void](Send-Cdp "Emulation.setEmulatedMedia" @{
        features = @(@{
            name = "prefers-reduced-motion"
            value = "reduce"
        })
    })
    $motion = Get-BrowserValue @'
({
    matches: matchMedia("(prefers-reduced-motion: reduce)").matches,
    scroll: getComputedStyle(document.documentElement).scrollBehavior,
    heroAnimation: getComputedStyle(document.querySelector(".lks-membership-hero__grid > div:first-child > *")).animationName,
    buttonTransition: getComputedStyle(document.querySelector(".lks-button")).transitionDuration
})
'@
    Add-Check ([bool]$motion.matches -and $motion.scroll -eq "auto") "Reduced-motion mode disables smooth scrolling"
    Add-Check ($motion.heroAnimation -eq "none") "Reduced-motion mode disables hero reveals"
    Add-Check ($motion.buttonTransition -in @("0s", "0.00001s", "1e-05s")) "Reduced-motion mode minimizes decorative transitions"

    [void](Send-Cdp "Emulation.setDeviceMetricsOverride" @{
        width = 1280
        height = 800
        deviceScaleFactor = 1
        mobile = $false
    })
    [void](Send-Cdp "Page.navigate" @{url = "$($BaseUrl.TrimEnd('/'))/"})
    Start-Sleep -Seconds 2
    $focusOrder = @()
    1..12 | ForEach-Object {
        Send-Key "Tab" "Tab" 9
        $focusOrder += Get-BrowserValue @'
({
    tag: document.activeElement.tagName,
    text: (document.activeElement.getAttribute("aria-label") || document.activeElement.textContent).trim().replace(/\s+/g, " "),
    href: document.activeElement.getAttribute("href"),
    visible: document.activeElement.getClientRects().length > 0
})
'@
    }
    Add-Check (($focusOrder | Where-Object { -not $_.visible }).Count -eq 0) "Desktop tab order contains only visible controls"
    $focusHrefs = $focusOrder.href
    Add-Check ($focusHrefs -contains "#main" -and ($focusHrefs -match "/meista/$").Count -ge 1 -and ($focusHrefs -match "/jaseneksi/$").Count -ge 1) "Desktop keyboard order reaches skip link, navigation, and membership CTA"

    $homeMetadata = Get-BrowserValue @'
(() => {
    const scripts = [...document.querySelectorAll('script[type="application/ld+json"]')];
    const schema = scripts.length === 1 ? JSON.parse(scripts[0].textContent) : {};
    const types = (schema["@graph"] || []).flatMap((node) => Array.isArray(node["@type"]) ? node["@type"] : [node["@type"]]).filter(Boolean);
    return {
        scripts: scripts.length,
        types,
        canonical: document.querySelector('link[rel="canonical"]')?.href,
        ogUrl: document.querySelector('meta[property="og:url"]')?.content,
        hasLocalUrl: scripts.some((script) => script.textContent.includes("lakeuden-kauppaseura.local"))
    };
})()
'@
    $missingHomeTypes = @("Organization", "WebSite", "WebPage", "BreadcrumbList") | Where-Object { $_ -notin $homeMetadata.types }
    Add-Check ($homeMetadata.scripts -eq 1 -and $missingHomeTypes.Count -eq 0) "Homepage exposes one graph with Organization, WebSite, WebPage, and BreadcrumbList"
    Add-Check ($homeMetadata.canonical -eq $homeMetadata.ogUrl -and -not $homeMetadata.hasLocalUrl) "Homepage canonical, Open Graph, and schema URLs are production-safe"

    [void](Send-Cdp "Page.navigate" @{url = "$($BaseUrl.TrimEnd('/'))/meista/"})
    Start-Sleep -Seconds 2
    $localBoard = Get-BrowserValue @'
(() => {
    const schema = JSON.parse(document.querySelector('script[type="application/ld+json"]').textContent);
    const types = (schema["@graph"] || []).flatMap((node) => Array.isArray(node["@type"]) ? node["@type"] : [node["@type"]]).filter(Boolean);
    return {
        cards: document.querySelectorAll(".lks-board-member-card").length,
        placeholders: document.querySelectorAll('.lks-board-member-card[data-lks-person-placeholder="true"]').length,
        fallbackAvatars: document.querySelectorAll(".lks-board-member-card .lks-person-avatar").length,
        personSchema: types.includes("Person")
    };
})()
'@
    Add-Check ($localBoard.cards -eq 8 -and $localBoard.placeholders -eq 8) "Local editing preview keeps all eight clearly marked board records visible"
    Add-Check ($localBoard.fallbackAvatars -ge 1) "Board cards without photographs use the neutral monogram fallback"
    Add-Check (-not $localBoard.personSchema) "Board placeholders do not generate Person structured data"

    [void](Send-Cdp "Page.navigate" @{url = "$($BaseUrl.TrimEnd('/'))/maatalous-tarvitsee-tuloksellisempaa-talousneuvontaa/"})
    Start-Sleep -Seconds 2
    $article = Get-BrowserValue @'
(() => {
    const schema = JSON.parse(document.querySelector('script[type="application/ld+json"]').textContent);
    const types = (schema["@graph"] || []).flatMap((node) => Array.isArray(node["@type"]) ? node["@type"] : [node["@type"]]).filter(Boolean);
    return {
        nav: [...document.querySelectorAll(".lks-article-nav a")].map((link) => ({
            text: link.textContent.trim().replace(/\s+/g, " "),
            tabIndex: link.tabIndex
        })),
        heroAlt: document.querySelector(".lks-article__hero-image img")?.alt,
        authorAlt: document.querySelector(".lks-author-card img")?.alt,
        schemaTypes: types
    };
})()
'@
    Add-Check ($article.heroAlt -and $article.heroAlt -notmatch "^Kirjoituksen kuvitus") "Article featured image uses media-library alt text"
    Add-Check ($article.authorAlt -eq "Heikki Kangas") "Author portrait uses media-library alt text"
    Add-Check ($article.nav.Count -ge 1 -and ($article.nav | Where-Object { $_.tabIndex -ne 0 }).Count -eq 0) "Article navigation links remain keyboard reachable"
    Add-Check ($article.schemaTypes -contains "BlogPosting") "Article preserves BlogPosting structured data"

    [void](Send-Cdp "Page.navigate" @{url = "$($BaseUrl.TrimEnd('/'))/tapahtumat/"})
    Start-Sleep -Seconds 2
    $events = Get-BrowserValue @'
(() => {
    const schema = JSON.parse(document.querySelector('script[type="application/ld+json"]').textContent);
    const types = (schema["@graph"] || []).flatMap((node) => Array.isArray(node["@type"]) ? node["@type"] : [node["@type"]]).filter(Boolean);
    return {
        cta: [...document.querySelectorAll(".lks-event-link")].slice(0, 3).map((link) => ({
            text: link.textContent.trim(),
            tabIndex: link.tabIndex,
            visible: link.getClientRects().length > 0,
            height: link.getBoundingClientRect().height
        })),
        dates: [...document.querySelectorAll(".lks-event-card time")].slice(0, 3).map((time) => ({
            text: time.textContent.trim(),
            datetime: time.dateTime
        })),
        calendarControls: document.querySelectorAll("[data-calendar], .calendar, [class*=calendar]").length,
        schemaTypes: types
    };
})()
'@
    Add-Check ($events.cta.Count -ge 1 -and ($events.cta | Where-Object { -not $_.visible -or $_.tabIndex -ne 0 }).Count -eq 0) "Event detail CTAs are visible and keyboard reachable"
    Add-Check (($events.cta | Where-Object { $_.height -lt 24 }).Count -eq 0) "Event detail CTAs meet the 24-pixel minimum target size"
    Add-Check ($events.dates.Count -ge 1 -and ($events.dates | Where-Object { -not $_.datetime }).Count -eq 0) "Event-card dates use machine-readable time elements"
    Add-Check ($events.calendarControls -eq 0) "No unsupported calendar controls are exposed"
    Add-Check ($events.schemaTypes -contains "WebPage" -and $events.schemaTypes -notcontains "Event") "Events archive exposes WebPage data without misrepresenting the archive as one Event"

    [void](Send-Cdp "Page.navigate" @{url = "$($BaseUrl.TrimEnd('/'))/tapahtuma/syksyn-verkostoitumisilta-seinajoella/"})
    Start-Sleep -Seconds 2
    $eventMetadata = Get-BrowserValue @'
(() => {
    const schema = JSON.parse(document.querySelector('script[type="application/ld+json"]').textContent);
    const event = (schema["@graph"] || []).find((node) => node["@type"] === "Event");
    return {
        hasEvent: Boolean(event),
        name: event?.name,
        description: event?.description,
        startDate: event?.startDate,
        status: event?.eventStatus,
        attendance: event?.eventAttendanceMode,
        location: event?.location,
        organizer: event?.organizer,
        url: event?.url,
        image: event?.image || null,
        offer: event?.offers || null,
        placeholder: JSON.stringify(event || {}).includes("[VAHVISTETAAN]"),
        publicState: document.querySelector(".lks-event-registration")?.dataset.lksEventState,
        publicMessage: document.querySelector(".lks-event-registration p")?.textContent.trim(),
        registrationActions: document.querySelectorAll(".lks-event-registration__action").length,
        registrationPanels: document.querySelectorAll(".lks-event-registration").length,
        statusBadges: document.querySelectorAll(".lks-event-status--header").length,
        forms: document.querySelectorAll(".lks-event-single form").length,
        heroImages: document.querySelectorAll(".lks-article__hero-image img").length,
        timeFact: [...document.querySelectorAll(".lks-event-facts > div")].find((item) => item.querySelector("dt")?.textContent.trim() === "Aika")?.querySelector("dd")?.textContent.trim()
    };
})()
'@
    Add-Check ($eventMetadata.hasEvent -and $eventMetadata.name -and $eventMetadata.description -and $eventMetadata.startDate -and $eventMetadata.organizer -and $eventMetadata.url) "Event page exposes required Event structured data"
    Add-Check ($eventMetadata.status -eq "https://schema.org/EventScheduled" -and $eventMetadata.attendance -eq "https://schema.org/OfflineEventAttendanceMode") "Event state and attendance mode use Schema.org URLs"
    Add-Check (-not $eventMetadata.placeholder) "Event structured data omits unresolved placeholders"
    Add-Check ($eventMetadata.registrationPanels -eq 0 -and $eventMetadata.statusBadges -eq 0) "Standard event without required registration shows no registration panel or status"
    Add-Check ($eventMetadata.registrationActions -eq 0 -and $null -eq $eventMetadata.offer -and $eventMetadata.forms -eq 0) "Standard event exposes no form, registration action, or Offer"
    Add-Check ($eventMetadata.heroImages -eq 0 -and $null -eq $eventMetadata.image -and [bool]$eventMetadata.timeFact) "Event without a featured image or time renders safely and omits an invented schema image"

    [void](Send-Cdp "Emulation.setDeviceMetricsOverride" @{
        width = 390
        height = 844
        deviceScaleFactor = 1
        mobile = $true
    })
    [void](Send-Cdp "Page.navigate" @{url = "$($BaseUrl.TrimEnd('/'))/tapahtuma/syksyn-verkostoitumisilta-seinajoella/"})
    Start-Sleep -Seconds 2
    $mobileEvent = Get-BrowserValue @'
(() => {
    const title = document.querySelector(".lks-event-single h1");
    const titleRect = title?.getBoundingClientRect();
    const timeFact = [...document.querySelectorAll(".lks-event-facts > div")].find((item) => item.querySelector("dt")?.textContent.trim() === "Aika");
    return {
        overflow: document.documentElement.scrollWidth > window.innerWidth + 1,
        viewportWidth: window.innerWidth,
        registrationPanels: document.querySelectorAll(".lks-event-registration").length,
        statusBadges: document.querySelectorAll(".lks-event-status--header").length,
        titleFits: Boolean(titleRect && titleRect.left >= 0 && titleRect.right <= window.innerWidth + 1),
        titleVisible: Boolean(title?.getClientRects().length),
        timeVisible: Boolean(timeFact?.getClientRects().length)
    };
})()
'@
    Add-Check (-not $mobileEvent.overflow -and $mobileEvent.titleFits) "Standard event fits the 390-pixel mobile viewport"
    Add-Check ($mobileEvent.registrationPanels -eq 0 -and $mobileEvent.statusBadges -eq 0) "Standard mobile event omits registration-only UI"
    Add-Check ($mobileEvent.titleVisible -and $mobileEvent.timeVisible) "Event title and confirmed date remain visible on mobile"

    [void](Send-Cdp "Emulation.setDeviceMetricsOverride" @{
        width = 1280
        height = 800
        deviceScaleFactor = 1
        mobile = $false
    })

    [void](Send-Cdp "Page.navigate" @{url = "$($BaseUrl.TrimEnd('/'))/tietosuoja/"})
    Start-Sleep -Seconds 2
    $privacy = Get-BrowserValue @'
(() => {
    const main = document.querySelector("#main");
    const text = main.textContent.replace(/\s+/g, " ");
    const schema = JSON.parse(document.querySelector('script[type="application/ld+json"]').textContent);
    const types = (schema["@graph"] || []).flatMap((node) => Array.isArray(node["@type"]) ? node["@type"] : [node["@type"]]).filter(Boolean);
    return {
        h1: main.querySelectorAll("h1").length,
        forms: main.querySelectorAll("form").length,
        legalMarkers: main.querySelectorAll('[data-lks-legal-review="required"]').length,
        hasMembership: text.includes("senyyskiinnostus"),
        hasEvents: text.includes("Tapahtumiin ilmoittautuminen"),
        hasNotifications: text.includes("Tapahtumailmoitusten tilaus"),
        optionalMarketing: text.includes("Vapaaehtoinen toiminta- ja tapahtumaviestint") && text.includes("tapahtumailmoittautumisen edellytys"),
        hasComplaintLink: Boolean(main.querySelector('a[href*="tietosuoja.fi/ilmoitus-tietosuojavaltuutetulle"]')),
        schemaTypes: types
    };
})()
'@
    Add-Check ($privacy.h1 -eq 1 -and $privacy.forms -eq 0) "Privacy page renders one H1 and no misleading public form"
    Add-Check ($privacy.hasMembership -and $privacy.hasEvents -and $privacy.hasNotifications) "Privacy page distinguishes membership, event registration, and absent notification processing"
    Add-Check ($privacy.optionalMarketing) "Privacy page makes optional communications consent independent of required processing"
    Add-Check ($privacy.hasComplaintLink -and $privacy.legalMarkers -ge 2) "Privacy page links to the Finnish authority and retains legal-review markers"
    Add-Check ($privacy.schemaTypes -contains "WebPage") "Privacy page exposes WebPage structured data"
}
finally {
    if ($socket) {
        try {
            $socket.Dispose()
        } catch {
        }
    }
    if ($edgeProcess -and -not $edgeProcess.HasExited) {
        Stop-Process -Id $edgeProcess.Id -Force
    }

    if (
        $profile.StartsWith($tempRoot, [StringComparison]::OrdinalIgnoreCase) -and
        $profile -ne $tempRoot -and
        (Test-Path -LiteralPath $profile)
    ) {
        for ($attempt = 1; $attempt -le 10; $attempt++) {
            if (-not (Test-Path -LiteralPath $profile)) {
                break
            }

            try {
                Remove-Item -LiteralPath $profile -Recurse -Force -ErrorAction Stop
                break
            } catch {
                if ($attempt -eq 10) {
                    Write-Warning "Could not fully remove the temporary Edge profile: $profile"
                    break
                }
                Start-Sleep -Milliseconds 250
            }
        }
    }
}

if ($failures.Count) {
    Write-Host ""
    Write-Host "$($failures.Count) browser accessibility check(s) failed."
    exit 1
}

Write-Host ""
Write-Host "Browser accessibility checks passed."
