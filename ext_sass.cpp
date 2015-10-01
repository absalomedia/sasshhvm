/*
   +----------------------------------------------------------------------+
   | HipHop for PHP                                                       |
   +----------------------------------------------------------------------+
   | Copyright (c) 2010-2013 Facebook, Inc. (http://www.facebook.com)     |
   +----------------------------------------------------------------------+
   | This source file is subject to version 3.01 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available through the world-wide-web at the following url:           |
   | http://www.php.net/license/3_01.txt                                  |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
*/

#include "hphp/runtime/ext/extension.h"
#include "hphp/runtime/base/array-init.h"
#include "hphp/runtime/base/string-util.h"
#include "hphp/runtime/base/execution-context.h"
#include "hphp/runtime/version.h"
#include "lib/libsass/sass_context.h"

#define IMPLEMENT_GET_CLASS(cls) \
  Class* cls::getClass() { \
    if (s_class == nullptr) { \
      s_class = Unit::lookupClass(s_className.get()); \
      assert(s_class); \
    } \
    return s_class; \
  }

namespace HPHP {

const StaticString s_Sass("Sass");

const StaticString s_STYLE_NESTED("STYLE_NESTED");
const StaticString s_STYLE_EXPANDED("STYLE_EXPANDED");
const StaticString s_STYLE_COMPACT("STYLE_COMPACT");
const StaticString s_STYLE_COMPRESSED("STYLE_COMPRESSED");

const StaticString s_SassException("SassException");
#ifdef __WIN__
const StaticString s_Glue(";");
#else
const StaticString s_Glue(",");
#endif

static Class* c_SassException = nullptr;
static Object throwSassExceptionObject(const Variant& message, int64_t code) {
  if (!c_SassException) {
    c_SassException = Unit::lookupClass(s_SassException.get());
    assert(c_SassException);
  }

  Object obj{c_SassException};
  TypedValue ret;
  g_context->invokeFunc(&ret,
                        c_SassException->getCtor(),
                        make_packed_array(message, code),
                        obj.get());
  tvRefcountedDecRef(&ret);
  throw obj;
}

static void set_options(ObjectData* obj, struct Sass_Context *ctx) {
  struct Sass_Options* opts = sass_context_get_options(ctx);

  // All options have been validated in ext_sass.php
  sass_option_set_precision(opts, obj->o_get("precision", true, s_Sass).toInt64Val());
  sass_option_set_output_style(opts, (Sass_Output_Style)obj->o_get("style", true, s_Sass).toInt64Val());
  Array includePaths = obj->o_get("includePaths", true, s_Sass).toCArrRef();
  if (!includePaths.empty()) {
    sass_option_set_include_path(opts, StringUtil::Implode(includePaths, s_Glue).c_str());
  }
  Bool commentsType = obj->o_get("comments", true, s_Sass).toBoolean();
  sass_option_set_source_comments(opts, obj->o_get(commentsType, true, s_Sass).toBoolean());
  if (!commentsType.empty()) {
  sass_option_set_omit_source_map_url(opts, false);
  }
  sass_option_set_source_map_embed(opts, obj->o_get("map_embed", true, s_Sass).toBoolean());
  sass_option_set_source_map_contents(opts, obj->o_get("map_contents", true, s_Sass).toBoolean());
  String mapLink = String::FromCStr(obj->o_get("map_path", true, s_Sass).c_str());
  if (!mapLink.empty()) {
  sass_option_set_source_map_file(opts, obj->o_get(mapLink, true, s_Sass).c_str());
  sass_option_set_omit_source_map_url(opts, false);
  sass_option_set_source_map_contents(opts, true);
  }
  String mapRoot = String::FromCStr(obj->o_get("map_root", true, s_Sass).c_str()); 
  if (!mapRoot.empty()) {
  sass_option_set_source_map_root(opts, obj->o_get(mapRoot, true, s_Sass).c_str());
  }

}

static String HHVM_METHOD(Sass, compile, const String& source) {
  // Create a new sass_context
  struct Sass_Data_Context* data_context = sass_make_data_context(strdup(source.c_str()));
  struct Sass_Context* ctx = sass_data_context_get_context(data_context);

  set_options(this_, ctx);

  int64_t status = sass_compile_data_context(data_context);

  // Check the context for any errors...
  if (status != 0) {
    String exMsg = String::FromCStr(sass_context_get_error_message(ctx));
    sass_delete_data_context(data_context);

    throwSassExceptionObject(exMsg, status);
  }

  String rt = String::FromCStr(sass_context_get_output_string(ctx));
  sass_delete_data_context(data_context);

  return rt;
}

static String HHVM_METHOD(Sass, compileFileNative, const String& file) {
  // Create a new sass_context
  struct Sass_File_Context* file_ctx = sass_make_file_context(file.c_str());
  struct Sass_Context* ctx = sass_file_context_get_context(file_ctx);

  Array return_value;

  set_options(this_, ctx);

  int64_t status = sass_compile_file_context(file_ctx);

  // Check the context for any errors...
  if (status != 0) {
    String exMsg = String::FromCStr(sass_context_get_error_message(ctx));
    sass_delete_file_context(file_ctx);

    throwSassExceptionObject(exMsg, status);
  } else {

    if (ctx.map_path.len > 0) {
    // Send it over to HHVM.
    add_next_index_string(return_value, sass_context_get_output_string(ctx), 1);
    } else {
    String rt = String::FromCStr(sass_context_get_output_string(ctx));
    return rt;
    }
    // Do we have source maps to go?
    if (ctx.map_path.len > 0)
    {
    // Send it over to PHP.
    add_next_index_string(return_value, sass_context_get_source_map_string(ctx), 1);

    }

   }
  sass_delete_file_context(file_ctx);
}

static String HHVM_STATIC_METHOD(Sass, getLibraryVersion) {
  return libsass_version();
}

static class SassExtension : public Extension {
 public:
  SassExtension() : Extension("sass", "0.2-dev") {}
  virtual void moduleInit() {
    HHVM_ME(Sass, compile);
    HHVM_ME(Sass, compileFileNative);
    HHVM_STATIC_ME(Sass, getLibraryVersion);

    Native::registerClassConstant<KindOfInt64>(s_Sass.get(),
                                               s_STYLE_NESTED.get(),
                                               SASS_STYLE_NESTED);
    Native::registerClassConstant<KindOfInt64>(s_Sass.get(),
                                               s_STYLE_EXPANDED.get(),
                                               SASS_STYLE_EXPANDED);
    Native::registerClassConstant<KindOfInt64>(s_Sass.get(),
                                               s_STYLE_COMPACT.get(),
                                               SASS_STYLE_COMPACT);
    Native::registerClassConstant<KindOfInt64>(s_Sass.get(),
                                               s_STYLE_COMPRESSED.get(),
                                               SASS_STYLE_COMPRESSED);
    loadSystemlib();
  }
} s_sass_extension;

HHVM_GET_MODULE(sass)

} // namespace HPHP
