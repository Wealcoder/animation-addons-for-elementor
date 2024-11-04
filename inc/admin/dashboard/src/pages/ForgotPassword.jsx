import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { z } from "zod";

import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { RiArrowLeftLine, RiMailLine } from "react-icons/ri";
import { cn } from "@/lib/utils";

const FormSchema = z.object({
  email: z.string().email(),
});

const ForgotPassword = () => {
  const form = useForm({
    resolver: zodResolver(FormSchema),
    defaultValues: {
      email: "",
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
      <div className="ps-[80px] pt-[54px] pe-[122px]">
        <div className="mb-6">
          <h3 className="text-2xl font-medium">Forgot Password</h3>
          <p className="mt-1.5 text-text-secondary">
            Enter your email to receive a password reset link.
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

            <div>
              <Button
                type="submit"
                className="w-full h-12 mt-3 hover:shadow-auth-btn text-base"
              >
                Send Email
              </Button>
              <div className="mt-4">
                <a
                  href={"/login"}
                  className="text-sm text-text-secondary hover:text-text-secondary-hover font-medium flex justify-center gap-1.5"
                >
                  <RiArrowLeftLine size={20} /> Go Back{" "}
                </a>
              </div>
            </div>
          </form>
        </Form>
      </div>
    </div>
  );
};

export default ForgotPassword;
