import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { z } from "zod";

import { Button, buttonVariants } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { RiLockLine, RiMailLine, RiSkipRightLine } from "react-icons/ri";
import { Link } from "react-router-dom";
import { cn } from "@/lib/utils";
import { Checkbox } from "@/components/ui/checkbox";

const FormSchema = z.object({
  email: z.string().email(),
  password: z.string(),
});

const Login = () => {
  const form = useForm({
    resolver: zodResolver(FormSchema),
    defaultValues: {
      email: "",
      password: "",
    },
  });

  function onSubmit(data) {
    console.log(data);
  }

  return (
    <div className="bg-background w-[1200px] h-[664px] overflow-hidden rounded-2xl p-2 grid grid-cols-2 shadow-auth-card">
      <div>
        <img
          src="/images/login-bg.png"
          className="object-cover w-[592px] h-[648px] rounded-xl"
          alt="auth bg"
        />
      </div>
      <div className="ps-[80px] pt-[54px] flex flex-col justify-between">
        <div className="pe-[122px]">
          <div className="mb-6">
            <h3 className="text-2xl font-medium">Log In</h3>
            <p className="mt-1.5 text-text-secondary">
              Get started by signing up to your account.
            </p>
          </div>
          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-3">
              <FormField
                control={form.control}
                name="email"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>
                      Email Address <span className="text-[#E15E35]">*</span>
                    </FormLabel>
                    <FormControl>
                      <div className="relative group">
                        <RiMailLine
                          size={20}
                          className={cn(
                            "absolute left-3 top-3 text-[#99A0AE] group-focus-within:text-icon",
                            field.value ? "text-icon" : ""
                          )}
                        />
                        <Input
                          placeholder="Enter your address"
                          className="h-11 ps-[38px] text-base file:text-base placeholder:text-[#99A0AE] shadow-common-2 group-hover:border-[#99A0AE] group-focus-within:shadow-auth-btn-2"
                          {...field}
                        />
                      </div>
                    </FormControl>

                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="password"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>
                      Password <span className="text-[#E15E35]">*</span>
                    </FormLabel>
                    <FormControl>
                      <div className="relative group">
                        <RiLockLine
                          size={20}
                          className={cn(
                            "absolute left-3 top-3 text-[#99A0AE] group-focus-within:text-icon",
                            field.value ? "text-icon" : ""
                          )}
                        />
                        <Input
                          placeholder="Enter password"
                          className="h-11 ps-[38px] text-base file:text-base placeholder:text-[#99A0AE] shadow-common-2 group-hover:border-[#99A0AE] group-focus-within:shadow-auth-btn-2"
                          {...field}
                        />
                      </div>
                    </FormControl>

                    <FormMessage />
                  </FormItem>
                )}
              />
              <div className="flex justify-between items-center gap-3">
                <div className="flex items-center space-x-2">
                  <Checkbox id="remember-me" />
                  <label
                    htmlFor="remember-me"
                    className="text-sm text-text-secondary peer-disabled:cursor-not-allowed"
                  >
                    Remember me
                  </label>
                </div>
                <Link
                  to={"/forgot-password"}
                  className="text-text-secondary hover:text-text-secondary-hover text-sm"
                >
                  Forgot Password?
                </Link>
              </div>
              <div>
                <Button
                  type="submit"
                  className="w-full h-12 mt-3 hover:shadow-auth-btn text-base"
                >
                  Log In
                </Button>
                <p className="mt-4 text-sm text-text-secondary text-center">
                  Already have an account?{" "}
                  <Link to={"/registration"} className="text-brand font-medium">
                    Sign Up
                  </Link>
                </p>
              </div>
            </form>
          </Form>
        </div>
        <div className="mt-[34px] me-4 mb-4 flex justify-end">
          <Link
            to={"/required-features"}
            className={cn(
              buttonVariants({ variant: "secondary" }),
              "pe-2 h-9 border-0 shadow-none"
            )}
          >
            Skip this step
            <RiSkipRightLine size={20} />
          </Link>
        </div>
      </div>
    </div>
  );
};

export default Login;
