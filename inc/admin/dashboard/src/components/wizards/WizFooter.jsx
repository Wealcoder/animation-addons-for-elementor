import { Button } from "../ui/button";

const WizFooter = () => {
  return (
    <div className="p-6 bg-white flex justify-end items-center gap-3 shadow-[0px_-2px_8px_0px_rgba(10,13,20,0.06)] z-20 relative">
      <Button variant="secondary" className="ps-[14px] pe-[18px]">
        <span className="flex items-center justify-center">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="20"
            height="20"
            viewBox="0 0 20 20"
            fill="none"
          >
            <path
              d="M8.87518 10.0006L13 5.87577L11.8215 4.69727L6.51818 10.0006L11.8215 15.3038L13 14.1253L8.87518 10.0006Z"
              fill="#525866"
            />
          </svg>
        </span>
        Go back
      </Button>
      <Button className="ps-[18px] pe-[14px]">
        Continue{" "}
        <span className="flex items-center justify-center">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="20"
            height="20"
            viewBox="0 0 20 20"
            fill="none"
          >
            <path
              d="M11.1248 10.0006L7 5.87577L8.17852 4.69727L13.4818 10.0006L8.17852 15.3038L7 14.1253L11.1248 10.0006Z"
              fill="white"
            />
          </svg>
        </span>
      </Button>
    </div>
  );
};

export default WizFooter;
