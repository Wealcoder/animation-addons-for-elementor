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
import { Checkbox } from "@/components/ui/checkbox";

const FormSchema = z.object({
  smooth: z.number().optional(),
  mobile: z.boolean().optional(),
});

const ScrollSmootherSettings = () => {
  const form = useForm({
    resolver: zodResolver(FormSchema),
    defaultValues: {
      smooth: 1.35,
      mobile: false,
    },
  });

  function onSubmit(data) {
    console.log(data);
  }

  return (
    <div className="py-5">
      <div className="px-6 pb-4 border-b border-border">
        <h2 className="text-xl text-text font-medium">Smooth Scroller</h2>
        <p className="text-sm text-text-secondary mt-1">
          Enter Smooth Scroller value below.
        </p>
      </div>
      <div>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-3">
            <FormField
              control={form.control}
              name="smooth"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>
                    Password <span className="text-[#E15E35]">*</span>
                  </FormLabel>
                  <FormControl>
                    <Input
                      placeholder="Enter password"
                      type="number"
                      className="h-11 text-base"
                      {...field}
                    />
                  </FormControl>

                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="mobile"
              render={({ field }) => (
                <FormItem className="flex flex-row items-start space-x-3 space-y-0">
                  <FormControl>
                    <Checkbox
                      checked={field.value}
                      onCheckedChange={field.onChange}
                    />
                  </FormControl>
                  <div className="space-y-1 leading-none">
                    <FormLabel>Remember me</FormLabel>
                  </div>
                </FormItem>
              )}
            />
            {/* <div className="flex items-center space-x-2">
              <Checkbox id="remember-me" />
              <label
                htmlFor="remember-me"
                className="text-sm text-text-secondary peer-disabled:cursor-not-allowed"
              >
                Remember me
              </label>
            </div> */}

            <div>
              <Button
                type="submit"
                className="h-12 mt-3 hover:shadow-auth-btn text-base"
              >
                Log In
              </Button>
            </div>
          </form>
        </Form>
      </div>
    </div>
  );
};

export default ScrollSmootherSettings;
