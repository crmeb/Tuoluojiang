const searchParams = new URLSearchParams(window.location.search);

if (searchParams.has("invitation")) {
  const invitationData = {
    invitation: searchParams.get("invitation"),
    entId: searchParams.get("enterprise"),
  };
  localStorage.setItem("invitationStorage", JSON.stringify(invitationData));
}
