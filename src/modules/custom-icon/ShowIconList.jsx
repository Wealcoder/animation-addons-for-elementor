const ShowIconList = () => {
  if (!WCF_ADDONS_ADMIN.custom_icon?.name) return;

  const deleteIcon = async () => {
    try {
      await fetch(WCF_ADDONS_ADMIN.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        body: new URLSearchParams({
          action: "aaeaddon_update_custom_icon_delete",
          id: WCF_ADDONS_ADMIN.id,
          nonce: WCF_ADDONS_ADMIN.nonce,
        }),
      })
        .then((response) => {
          return response.json();
        })
        .then((return_content) => {
          console.log("Animation Delete Successfully");
        });
    } catch (error) {
      console.log(error);
    }
  };

  return (
    <div className="show-icon-list">
      <div>
        <p>{WCF_ADDONS_ADMIN.custom_icon?.name}</p>
      </div>
      <div>
        <button onClick={() => deleteIcon()}>Remove</button>
      </div>
    </div>
  );
};

export default ShowIconList;
