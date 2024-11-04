import { Link } from "react-router-dom";

const ShortLogo = () => {
  return (
    <Link to={"/"}>
      <img width={40} height={40} src="/images/Logo.png" alt="Logo" />
    </Link>
  );
};

export default ShortLogo;
