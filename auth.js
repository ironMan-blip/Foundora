
(() => {
  const qs = (id) => document.getElementById(id);

  const authModal = qs("authModal");
  const authBackdrop = qs("authBackdrop");
  const authClose = qs("authClose");
  const authBtn = qs("authBtn");
  const authBtnMobile = qs("authBtnMobile");
  const joinFoundoraBtn = qs("joinFoundoraBtn");

  const modeSignup = qs("modeSignup");
  const modeLogin = qs("modeLogin");
  const roleToggle = qs("roleToggle");
  const roleStartup = qs("roleStartup");
  const roleInvestor = qs("roleInvestor");

  const authTitle = qs("authTitle");
  const submitText = qs("submitText");
  const authAction = qs("authAction");
  const authRole = qs("authRole");

  const nameWrap = qs("nameWrap");
  const nameInput = qs("f_name");

  const startupFields = qs("startupFields");
  const investorFields = qs("investorFields");
  const confirmWrap = qs("confirmWrap");
  const passConfirm = qs("f_password_confirm");

  const msgBox = qs("authMsg");

  if (!authModal) return;

  const openAuth = () => {
    authModal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
    msgBox?.classList.add("hidden");
    if (msgBox) msgBox.textContent = "";
  };

  const closeAuthFn = () => {
    authModal.classList.add("hidden");
    document.body.style.overflow = "";
  };

  authBtn?.addEventListener("click", openAuth);
  authBtnMobile?.addEventListener("click", () => {
    try { if (window.mobileMenu) window.mobileMenu.classList.add("hidden"); } catch (_) {}
    openAuth();
  });

  authBackdrop?.addEventListener("click", closeAuthFn);
  authClose?.addEventListener("click", closeAuthFn);

  window.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !authModal.classList.contains("hidden")) closeAuthFn();
  });

  const req = (id, on) => { const el = qs(id); if (el) el.required = !!on; };

  const setMode = (mode) => {
    const isSignup = mode === "signup";

    if (isSignup) {
      modeSignup.classList.add("bg-plum", "text-white");
      modeSignup.classList.remove("text-slate-700");
      modeLogin.classList.remove("bg-plum", "text-white");
      modeLogin.classList.add("text-slate-700");
    } else {
      modeLogin.classList.add("bg-plum", "text-white");
      modeLogin.classList.remove("text-slate-700");
      modeSignup.classList.remove("bg-plum", "text-white");
      modeSignup.classList.add("text-slate-700");
    }

    authAction.value = mode;
    authTitle.textContent = isSignup ? "Create your account" : "Welcome back";
    submitText.textContent = isSignup ? "Create account" : "Log in";

    // LOGIN = only Email + Password (no name, no confirm, no role fields)
    roleToggle?.classList.toggle("hidden", !isSignup);

    nameWrap?.classList.toggle("hidden", !isSignup);
    if (nameInput) nameInput.required = isSignup;

    confirmWrap?.classList.toggle("hidden", !isSignup);
    if (passConfirm) passConfirm.required = isSignup;

    startupFields?.classList.toggle("hidden", !isSignup || authRole.value !== "startup");
    investorFields?.classList.toggle("hidden", !isSignup || authRole.value !== "investor");

    // Required toggles for profile fields only in signup
    setRole(authRole.value, isSignup);
  };

  const setRole = (role, isSignupOverride = null) => {
    const isSignup = (isSignupOverride === null) ? (authAction.value === "signup") : isSignupOverride;
    const isStartup = role === "startup";

    if (isStartup) {
      roleStartup?.classList.add("bg-plum", "text-white");
      roleStartup?.classList.remove("text-slate-700");
      roleInvestor?.classList.remove("bg-plum", "text-white");
      roleInvestor?.classList.add("text-slate-700");
    } else {
      roleInvestor?.classList.add("bg-plum", "text-white");
      roleInvestor?.classList.remove("text-slate-700");
      roleStartup?.classList.remove("bg-plum", "text-white");
      roleStartup?.classList.add("text-slate-700");
    }

    authRole.value = role;

    if (startupFields) startupFields.classList.toggle("hidden", !isSignup || !isStartup);
    if (investorFields) investorFields.classList.toggle("hidden", !isSignup || isStartup);

    // Startup_Profile required fields (only on signup + startup)
    ["f_startup_name","f_founder_name","f_industry","f_stage","f_description","f_funding_needed"]
      .forEach((id) => req(id, isSignup && isStartup));

    // Investor_Profile required fields (only on signup + investor)
    ["f_investor_name","f_investor_type","f_investor_range","f_sector_of_interest"]
      .forEach((id) => req(id, isSignup && !isStartup));
  };

  modeSignup?.addEventListener("click", () => setMode("signup"));
  modeLogin?.addEventListener("click", () => setMode("login"));
  roleStartup?.addEventListener("click", () => setRole("startup"));
  roleInvestor?.addEventListener("click", () => setRole("investor"));

  // Hero CTA -> open modal directly on Sign up
  joinFoundoraBtn?.addEventListener("click", () => {
    setMode("signup");
    openAuth();
  });

  // Defaults
  authRole.value = "startup";
  setMode("signup");
  setRole("startup");

  // Front-end validation: password match only for signup
  qs("authForm")?.addEventListener("submit", (e) => {
    if (authAction.value !== "signup") return;
    const p1 = qs("f_password")?.value || "";
    const p2 = qs("f_password_confirm")?.value || "";
    if (p1 !== p2) {
      e.preventDefault();
      if (msgBox) {
        msgBox.className = "mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-[12px] text-rose-800";
        msgBox.textContent = "Passwords do not match.";
        msgBox.classList.remove("hidden");
      }
    }
  });

  // Demo auth (front-end only): save to localStorage and redirect to dashboard
  const form = qs("authForm");
  // Demo submit handler removed: allow normal form POST to auth.php

})();
