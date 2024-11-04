import { Link } from "react-router-dom";

const LargeLogo = () => {
  return (
    <Link to={"/"}>
      <img width={140} height={40} src="/images/Logo-2.png" alt="Logo" />
    </Link>
  );
};

export default LargeLogo;
