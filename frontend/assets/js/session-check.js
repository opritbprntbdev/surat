fetch("../backend/api/check_session.php")
  .then((res) => res.json())
  .then((data) => {
    if (!data.logged_in) {
      window.location.href = "login.html";
    }
  });
