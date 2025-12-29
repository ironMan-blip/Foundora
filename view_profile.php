<?php
require_once "auth_check.php";
require_once "database.php";

/*
  Accept both:
   - view_profile.php?id=3
   - view_profile.php?id=u3
*/
$idRaw = (string)($_GET["id"] ?? "");
$targetId = (int)preg_replace('/\D/', '', $idRaw);

if ($targetId <= 0) {
    die("Invalid user ID");
}

// Safe HTML output
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

/* ---------- Fetch basic user info ---------- */
$sql = "SELECT User_id, Name, Email, User_type FROM User WHERE User_id = ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) die("DB prepare failed (User)");

mysqli_stmt_bind_param($stmt, "i", $targetId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

if (!$res || mysqli_num_rows($res) !== 1) {
    die("User not found");
}

$user = mysqli_fetch_assoc($res);
$role = strtolower($user["User_type"] ?? "");

/* ---------- Fetch role-specific profile ---------- */
$profile = [];

if ($role === "startup") {
    $sql = "SELECT Startup_name, Founder_name, Industry, Description, Stage, Funding_needed
            FROM Startup_Profile
            WHERE User_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) die("DB prepare failed (Startup_Profile)");

    mysqli_stmt_bind_param($stmt, "i", $targetId);
    mysqli_stmt_execute($stmt);
    $res2 = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if ($res2 && mysqli_num_rows($res2) === 1) {
        $profile = mysqli_fetch_assoc($res2);
    }

} elseif ($role === "investor") {
    $sql = "SELECT Investor_name, Investor_type, Investor_range, Sector_of_interest
            FROM Investor_Profile
            WHERE User_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) die("DB prepare failed (Investor_Profile)");

    mysqli_stmt_bind_param($stmt, "i", $targetId);
    mysqli_stmt_execute($stmt);
    $res2 = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if ($res2 && mysqli_num_rows($res2) === 1) {
        $profile = mysqli_fetch_assoc($res2);
    }

} else {
    die("Unknown user type");
}

/* ---------- Choose a nice display title ---------- */
$title = $user["Name"] ?? "User";
if ($role === "startup" && !empty($profile["Startup_name"])) {
    $title = $profile["Startup_name"];
}
if ($role === "investor" && !empty($profile["Investor_name"])) {
    $title = $profile["Investor_name"];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>View Profile</title>

  <!-- Same Tailwind CDN approach as profile.html -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            ink: "#2B1E4A",
            plum: "#5B2CFF",
            lavender: "#BFA9FF"
          },
          boxShadow: {
            soft: "0 20px 50px rgba(20, 10, 55, .10)"
          }
        }
      }
    }
  </script>
</head>

<body class="min-h-screen bg-[#F6F5FF] text-ink">
  <div class="max-w-6xl mx-auto px-4 py-8">

    <!-- Main card (similar vibe to your profile page cards) -->
    <div class="rounded-3xl border border-slate-100 bg-white/90 backdrop-blur shadow-soft p-6">
      <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
          <p class="text-xs text-slate-500 font-bold">PUBLIC PROFILE VIEW</p>
          <h1 class="text-xl sm:text-2xl font-extrabold truncate"><?php echo h($title); ?></h1>
          <p class="text-sm text-slate-500 mt-1">
            <?php echo strtoupper(h($role)); ?>
          </p>
        </div>

        <div class="flex gap-2">
          <a href="./search.php"
             class="px-4 py-2 rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 text-sm font-extrabold transition">
            ← Back
          </a>

          <!-- Opens modal -->
          <button id="btnOpenModal"
             class="px-4 py-2 rounded-2xl bg-plum text-white text-sm font-extrabold hover:opacity-95 transition">
            View details
          </button>
        </div>
      </div>

      <!-- Quick summary grid -->
      <div class="mt-6 grid sm:grid-cols-2 gap-3">

        <?php if ($role === "startup"): ?>
          <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
            <p class="text-xs font-extrabold text-slate-500">Founder Name</p>
            <p class="text-sm font-extrabold mt-1"><?php echo h($profile["Founder_name"] ?? ""); ?></p>
          </div>

          <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
            <p class="text-xs font-extrabold text-slate-500">Industry</p>
            <p class="text-sm font-extrabold mt-1"><?php echo h($profile["Industry"] ?? ""); ?></p>
          </div>

          <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
            <p class="text-xs font-extrabold text-slate-500">Stage</p>
            <p class="text-sm font-extrabold mt-1"><?php echo h($profile["Stage"] ?? ""); ?></p>
          </div>

          <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
            <p class="text-xs font-extrabold text-slate-500">Funding Needed</p>
            <p class="text-sm font-extrabold mt-1"><?php echo h($profile["Funding_needed"] ?? ""); ?></p>
          </div>

          <div class="sm:col-span-2 rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
            <p class="text-xs font-extrabold text-slate-500">Description</p>
            <p class="text-sm mt-1"><?php echo h($profile["Description"] ?? ""); ?></p>
          </div>

        <?php else: ?>
          <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
            <p class="text-xs font-extrabold text-slate-500">Investor Type</p>
            <p class="text-sm font-extrabold mt-1"><?php echo h($profile["Investor_type"] ?? ""); ?></p>
          </div>

          <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
            <p class="text-xs font-extrabold text-slate-500">Investor Range</p>
            <p class="text-sm font-extrabold mt-1"><?php echo h($profile["Investor_range"] ?? ""); ?></p>
          </div>

          <div class="sm:col-span-2 rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
            <p class="text-xs font-extrabold text-slate-500">Sector of Interest</p>
            <p class="text-sm mt-1"><?php echo h($profile["Sector_of_interest"] ?? ""); ?></p>
          </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- ===== Modal (same style concept as profile.html edit modal) ===== -->
    <div id="profileModal" class="fixed inset-0 z-50 hidden">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" id="modalBackdrop"></div>

      <!-- Modal card -->
      <div class="relative max-w-3xl mx-auto mt-10 sm:mt-16 px-4">
        <div class="rounded-3xl border border-slate-100 bg-white/90 backdrop-blur shadow-soft overflow-hidden">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
              <p class="text-sm font-extrabold text-ink">Profile details</p>
              <p class="text-xs text-slate-500 font-bold"><?php echo h($title); ?></p>
            </div>

            <button id="btnCloseModal" type="button"
              class="h-10 w-10 rounded-2xl border border-slate-200 hover:bg-slate-50 bg-white grid place-items-center transition"
              aria-label="Close">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M6 6l12 12M18 6L6 18" stroke="#2B1E4A" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
          </div>

          <!-- Body -->
          <div class="p-6 grid gap-4 max-h-[70vh] overflow-y-auto">
            <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
              <p class="text-xs font-extrabold text-slate-500">Display Name</p>
              <p class="text-sm font-extrabold mt-1"><?php echo h($user["Name"] ?? ""); ?></p>
            </div>

            <?php if ($role === "startup"): ?>
              <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
                <p class="text-xs font-extrabold text-slate-500">Startup Name</p>
                <p class="text-sm font-extrabold mt-1"><?php echo h($profile["Startup_name"] ?? ""); ?></p>
              </div>

              <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
                <p class="text-xs font-extrabold text-slate-500">Founder Name</p>
                <p class="text-sm font-extrabold mt-1"><?php echo h($profile["Founder_name"] ?? ""); ?></p>
              </div>

              <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
                <p class="text-xs font-extrabold text-slate-500">Industry</p>
                <p class="text-sm font-extrabold mt-1"><?php echo h($profile["Industry"] ?? ""); ?></p>
              </div>

              <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
                <p class="text-xs font-extrabold text-slate-500">Stage</p>
                <p class="text-sm font-extrabold mt-1"><?php echo h($profile["Stage"] ?? ""); ?></p>
              </div>

              <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
                <p class="text-xs font-extrabold text-slate-500">Funding Needed</p>
                <p class="text-sm font-extrabold mt-1"><?php echo h($profile["Funding_needed"] ?? ""); ?></p>
              </div>

              <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
                <p class="text-xs font-extrabold text-slate-500">Description</p>
                <p class="text-sm mt-1"><?php echo h($profile["Description"] ?? ""); ?></p>
              </div>

            <?php else: ?>
              <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
                <p class="text-xs font-extrabold text-slate-500">Investor Name</p>
                <p class="text-sm font-extrabold mt-1"><?php echo h($profile["Investor_name"] ?? ""); ?></p>
              </div>

              <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
                <p class="text-xs font-extrabold text-slate-500">Investor Type</p>
                <p class="text-sm font-extrabold mt-1"><?php echo h($profile["Investor_type"] ?? ""); ?></p>
              </div>

              <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
                <p class="text-xs font-extrabold text-slate-500">Investor Range</p>
                <p class="text-sm font-extrabold mt-1"><?php echo h($profile["Investor_range"] ?? ""); ?></p>
              </div>

              <div class="rounded-3xl border border-slate-100 bg-white/60 backdrop-blur p-4">
                <p class="text-xs font-extrabold text-slate-500">Sector of Interest</p>
                <p class="text-sm mt-1"><?php echo h($profile["Sector_of_interest"] ?? ""); ?></p>
              </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>

  </div>

<script>
  const modal = document.getElementById("profileModal");
  const openBtn = document.getElementById("btnOpenModal");
  const closeBtn = document.getElementById("btnCloseModal");
  const backdrop = document.getElementById("modalBackdrop");

  function openModal() { modal.classList.remove("hidden"); }
  function closeModal() { modal.classList.add("hidden"); }

  openBtn.addEventListener("click", openModal);
  closeBtn.addEventListener("click", closeModal);

  // click outside
  backdrop.addEventListener("click", closeModal);

  // Esc key closes
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeModal();
  });
</script>

</body>
</html>
