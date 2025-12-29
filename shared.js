
/* Foundora shared UI + mock data (front-end only) */
window.Foundora = (() => {
  const KEY_USER = "foundora_user";
  const KEY_SEEDED = "foundora_seeded_v1";
  const KEY_DATA = "foundora_data_v1";

  const getUser = () => {
    try { return JSON.parse(localStorage.getItem(KEY_USER) || "null"); }
    catch { return null; }
  };

  const setUser = (u) => localStorage.setItem(KEY_USER, JSON.stringify(u));
  const clearUser = () => localStorage.removeItem(KEY_USER);

  const seed = () => {
    if (localStorage.getItem(KEY_SEEDED)) return;
    const now = new Date();
    const data = {
      userProfiles: {},
      startups: [
        { id:"s1", name:"MintLoop", industry:"SaaS", stage:"Seed", location:"Dhaka", tagline:"Workflow automation for SMBs", needs:250000, match:92, verified:true },
        { id:"s2", name:"CarePulse", industry:"Health", stage:"Pre-seed", location:"Singapore", tagline:"Remote triage & care navigation", needs:400000, match:88, verified:true },
        { id:"s3", name:"FinNest", industry:"Fintech", stage:"MVP", location:"London", tagline:"Smart budgeting for Gen Z", needs:150000, match:81, verified:false },
      ],
      investors: [
        { id:"i1", name:"Apex Angels", type:"Angel", focus:"SaaS, Fintech", ticket:"$10k–$50k", location:"Dubai", match:90, verified:true },
        { id:"i2", name:"NorthBridge VC", type:"VC", focus:"Health, Climate", ticket:"$250k–$1M", location:"NYC", match:84, verified:true },
        { id:"i3", name:"Studio Syndicate", type:"Syndicate", focus:"Consumer, Web", ticket:"$25k–$150k", location:"Berlin", match:77, verified:false },
      ],
      conversations: [
        { id:"c1", with:"Apex Angels", last:"Love the traction—can we see your deck?", at: new Date(now.getTime()-3600*1000*7).toISOString(), unread:2, messages:[
          { by:"them", text:"Hey! Your profile looks interesting.", at:new Date(now.getTime()-3600*1000*9).toISOString() },
          { by:"me", text:"Thanks! Happy to share deck + metrics.", at:new Date(now.getTime()-3600*1000*8.5).toISOString() },
          { by:"them", text:"Love the traction—can we see your deck?", at:new Date(now.getTime()-3600*1000*7).toISOString() },
        ]},
        { id:"c2", with:"CarePulse", last:"We’re raising pre-seed. Interested?", at: new Date(now.getTime()-3600*1000*30).toISOString(), unread:0, messages:[
          { by:"them", text:"We’re raising pre-seed. Interested?", at:new Date(now.getTime()-3600*1000*30).toISOString() },
        ]},
      ],
      funding: [
        { id:"f1", counterpart:"Apex Angels", stage:"Intro", amount:0, updated:new Date(now.getTime()-3600*1000*10).toISOString(), next:"Share deck + KPI snapshot" },
        { id:"f2", counterpart:"NorthBridge VC", stage:"Meeting", amount:0, updated:new Date(now.getTime()-3600*1000*60).toISOString(), next:"Schedule partner meeting" },
        { id:"f3", counterpart:"Studio Syndicate", stage:"Due Diligence", amount:150000, updated:new Date(now.getTime()-3600*1000*120).toISOString(), next:"Upload data room link" },
      ],
      notifications: [
        { id:"n1", type:"match", title:"New investor match", body:"Apex Angels matches your preferences (92%).", at:new Date(now.getTime()-3600*1000*5).toISOString(), read:false },
        { id:"n2", type:"message", title:"New message", body:"NorthBridge VC: “Can you share your financial model?”", at:new Date(now.getTime()-3600*1000*14).toISOString(), read:false },
        { id:"n3", type:"meeting", title:"Meeting reminder", body:"Pitch call in 24 hours.", at:new Date(now.getTime()-3600*1000*26).toISOString(), read:true },
      ],
      meetings: [
        { id:"m1", with:"NorthBridge VC", when:new Date(now.getTime()+3600*1000*24).toISOString(), mode:"Google Meet", agenda:"Intro + product demo", status:"Scheduled" },
        { id:"m2", with:"Apex Angels", when:new Date(now.getTime()+3600*1000*72).toISOString(), mode:"Zoom", agenda:"Deck walkthrough", status:"Scheduled" },
      ],
    };

    localStorage.setItem(KEY_DATA, JSON.stringify(data));
    localStorage.setItem(KEY_SEEDED, "1");
  };

  const getData = () => {
    seed();
    try { return JSON.parse(localStorage.getItem(KEY_DATA) || "{}"); }
    catch { return {}; }
  };

  const setData = (data) => localStorage.setItem(KEY_DATA, JSON.stringify(data));

  const getProfiles = () => {
    const data = getData();
    if (!data.userProfiles) data.userProfiles = {};
    return data.userProfiles;
  };

  const getMyProfile = () => {
    const user = getUser();
    if (!user?.email) return null;
    const data = getData();
    data.userProfiles = data.userProfiles || {};
    return data.userProfiles[user.email] || null;
  };

  const saveMyProfile = (profile) => {
    const user = getUser();
    if (!user?.email) return;
    const data = getData();
    data.userProfiles = data.userProfiles || {};
    const prev = data.userProfiles[user.email] || {};
    data.userProfiles[user.email] = { ...prev, ...profile, email: user.email, role: profile.role || user.role, updatedAt: new Date().toISOString() };
    setData(data);
  };

  const deleteConversation = (convId) => {
    const data = getData();
    data.conversations = (data.conversations || []).filter(c => c.id !== convId);
    setData(data);
  };

  const ensureMyProfile = () => {
    const user = getUser();
    if (!user?.email) return;
    const data = getData();
    data.userProfiles = data.userProfiles || {};
    if (!data.userProfiles[user.email]) {
      data.userProfiles[user.email] = {
        email: user.email,
        role: user.role || "startup",
        displayName: user.name || "",
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
      };
      setData(data);
    }
  };


  const fmtTime = (iso) => {
    const d = new Date(iso);
    return d.toLocaleString(undefined, { weekday:"short", month:"short", day:"numeric", hour:"2-digit", minute:"2-digit" });
  };

  const requireAuth = () => {
    const user = getUser();
    if (!user) {
      window.location.href = "./index.html#home";
      return;
    }
    // Ensure profile container exists for this user
    ensureMyProfile();
  };

  const mountTopBar = () => {
    const applyUser = (user) => {
      const badge = document.getElementById("userBadge");
      const name = document.getElementById("userName");
      const role = document.getElementById("userRole");
      const logout = document.getElementById("logoutBtn");
      const backToLanding = document.getElementById("btnBackToLanding");

      // After login (dashboard area), hide the landing shortcut.
      if (backToLanding) backToLanding.classList.add("hidden");

      const initials = (user.name || "Foundora")
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((s) => s[0].toUpperCase())
        .join("");

      if (badge) badge.textContent = initials || "FD";
      if (name) name.textContent = user.name || "User";
      if (role) role.textContent = (user.role || "startup").toUpperCase();

      // Logout must hit the server to destroy the PHP session
      logout?.addEventListener("click", () => {
        // FIX: Real logout must destroy PHP session on the server.
        window.location.href = "./logout.php";
      });
    };

    // 1) Try localStorage first (fast path)
    const user = getUser();
    if (user) {
      applyUser(user);
      return;
    }

    // 2) FIX: If localStorage is empty (common right after redirect/login),
    // fetch the logged-in user from the PHP session using me.php,
    // then write to localStorage and update the topbar without needing a hard refresh.
    fetch("./me.php", { credentials: "same-origin" })
      .then((res) => (res.ok ? res.json() : null))
      .then((data) => {
        if (!data || !data.logged_in || !data.user) return;

        try {
          setUser({
            name: data.user.name,
            email: data.user.email,
            role: data.user.role,
            loggedInAt: new Date().toISOString(),
            mode: "session",
          });
        } catch (e) {}

        const u2 = getUser();
        if (u2) applyUser(u2);
      })
      .catch(() => {});
  };

  const mountBubbles = () => {
    // Add floating bubbles/blobs background (dashboard + other pages)
    if (document.getElementById("fdBubbles")) return;

    const styleId = "fdBubblesStyle";
    if (!document.getElementById(styleId)) {
      const s = document.createElement("style");
      s.id = styleId;
      s.textContent = `
        @keyframes fdFloatY { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-18px)} }
        @keyframes fdFloatX { 0%,100%{transform:translateX(0)} 50%{transform:translateX(14px)} }
        @keyframes fdFloatR { 0%,100%{transform:rotate(0deg)} 50%{transform:rotate(6deg)} }

        .fd-bubbles{ position:fixed; inset:0; pointer-events:none; z-index:-1; overflow:hidden; }
        .fd-bubbles .b{ position:absolute; filter: blur(0px); opacity:.55; border-radius: 62% 38% 55% 45% / 45% 55% 45% 55%; }
        .fd-bubbles .b2{ border-radius: 55% 45% 40% 60% / 55% 45% 55% 45%; opacity:.45; }
        .fd-bubbles .glow{ filter: blur(28px); opacity:.35; }
        .fd-bubbles .aY{ animation: fdFloatY 9s ease-in-out infinite; }
        .fd-bubbles .aX{ animation: fdFloatX 10s ease-in-out infinite; }
        .fd-bubbles .aR{ animation: fdFloatR 13s ease-in-out infinite; }

        /* Make sure background doesn't create scrollbars */
        body{ overflow-x:hidden; }
      `;
      document.head.appendChild(s);
    }

    const wrap = document.createElement("div");
    wrap.id = "fdBubbles";
    wrap.className = "fd-bubbles";
    wrap.setAttribute("aria-hidden", "true");

    // Use Tailwind-like colors already present in your pages (mint/cyan/lavender/plum accents)
    wrap.innerHTML = `
      <div class="b glow aY" style="left:-120px; top:-120px; width:360px; height:360px; background: rgba(123,224,195,.75);"></div>
      <div class="b b2 glow aR" style="right:-140px; top:120px; width:420px; height:420px; background: rgba(182,236,255,.75);"></div>
      <div class="b glow aX" style="left:18%; bottom:-180px; width:520px; height:520px; background: rgba(214,199,255,.70);"></div>
      <div class="b b2 aY" style="right:14%; bottom:18%; width:180px; height:180px; background: rgba(104,70,160,.35); filter: blur(14px);"></div>
      <div class="b aR" style="left:58%; top:10%; width:140px; height:140px; background: rgba(123,224,195,.45); filter: blur(10px);"></div>
      <div class="b b2 aX" style="left:6%; top:55%; width:120px; height:120px; background: rgba(182,236,255,.45); filter: blur(10px);"></div>
    `;
    document.body.appendChild(wrap);
  };


  const upsertMeeting = (meeting) => {
    const data = getData();
    data.meetings = data.meetings || [];
    data.meetings.unshift(meeting);
    setData(data);
  };

  const sendMessage = (convId, text) => {
    const data = getData();
    const conv = (data.conversations || []).find(c => c.id === convId);
    if (!conv) return;
    const msg = { by:"me", text, at: new Date().toISOString() };
    conv.messages = conv.messages || [];
    conv.messages.push(msg);
    conv.last = text;
    conv.at = msg.at;
    setData(data);
  };

  // Start (or open) a chat with a selected startup/investor from cards.
  // Creates a conversation if it doesn't exist, stores it as the active chat,
  // then navigates to the messages page.
  const startChat = (target) => {
    if (!target) return;

    // "with" can come from search results or profile cards.
    const withName = target.with || target.name || "";
    const withId = target.id || target.user_id || target.investor_id || target.startup_id || "";

    if (!withName && !withId) return;

    const data = getData();
    data.conversations = data.conversations || [];

    // Try to find an existing conversation by counterpart id first (best), then by name.
    let conv = null;
    if (withId) {
      conv = data.conversations.find(c => String(c.withId || "") === String(withId));
    }
    if (!conv && withName) {
      conv = data.conversations.find(c => (c.with || "").toLowerCase() === String(withName).toLowerCase());
    }

    if (!conv) {
      const nowIso = new Date().toISOString();
      conv = {
        id: "c" + Math.random().toString(16).slice(2),
        withId: withId ? String(withId) : "",
        with: withName || "",
        last: "",
        at: nowIso,
        unread: 0,
        messages: [],
      };
      data.conversations.unshift(conv);
      setData(data);
    } else {
      // Ensure stored conversation has id/name if we now know them.
      if (withId && !conv.withId) conv.withId = String(withId);
      if (withName && !conv.with) conv.with = withName;
      setData(data);
    }

    // Store "active chat" info so messages.html can auto-select a conversation.
    localStorage.setItem("foundora_active_chat", JSON.stringify({
      convId: conv.id,
      withId: conv.withId || "",
      with: conv.with || ""
    }));

    // Prefer query params (more reliable) but keep the localStorage fallback too.
    if (withId) {
      const qs = `?with=${encodeURIComponent(withId)}&name=${encodeURIComponent(conv.with || withName || "")}`;
      window.location.href = "./messages.html" + qs;
    } else {
      window.location.href = "./messages.html";
    }
  };

  const markAllRead = () => {
    const data = getData();
    (data.notifications || []).forEach(n => n.read = true);
    setData(data);
  };

  return {
    getUser, setUser, clearUser,
    getData, setData,
    fmtTime,
    requireAuth,
    mountTopBar,
    getMyProfile,
    saveMyProfile,
    ensureMyProfile,
    upsertMeeting,
    sendMessage,
    startChat,
    deleteConversation,
    markAllRead,
    mountBubbles,
  };
})();
