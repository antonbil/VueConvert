require "rexml/document"
require "xmlsimple"
require "yaml"
require "pathname"
require "fileutils"

class GenIM
	attr_reader :kvProps
	attr_reader :selectList, :rejectList
	attr_reader :mapHash
	attr_reader :prefix, :postfix
	attr_reader :offset_x, :offset_y, :scale_x, :scale_y
	
def initialize
	@kvProps = nil
	loadProperties
	
	begin
		@imFile = File.open(@imFileName, "w")
		puts "--- imFile created ---"
		traverse
	rescue SystemCallError => exception
		puts "Problems with file: #{exception}"
		raise
	ensure
		@imFile.close unless @imFile.nil?
	end
end
def loadProperties
	begin
		propFileName = "im.yml"
		if ARGV.size >= 1
			propFileName = ARGV[0]
		end
		puts "property file name = #{propFileName}"
		YAML::ENGINE.yamler = 'syck'
		@kvProps = YAML.load_file propFileName
		puts "--- properties---"
		@kvProps.each_pair { |key, value| puts "#{key} = #{value}" }
		puts "--- end properties---"
	rescue SystemCallError => exception
		puts "Problems with file: #{exception} ... does it exist?"
		raise
	end

	begin
		if !@kvProps.key?("vue_file_name")
			raise IndexError, "property: vue_file_name does not exist"
		end
		if !@kvProps.key?("im_file_name")
			raise IndexError, "property: im_file_name does not exist"
		end
		
		@vueFileName = @kvProps["vue_file_name"]
		@imFileName = @kvProps["im_file_name"]
		
		vueFile = File.new(@vueFileName)
		@vueDoc = REXML::Document.new(vueFile)
		puts "--- vueDoc dom tree created ---"
	rescue IndexError => exception
		puts "IndexError: #{exception}"
		raise
	rescue SystemCallError => exception
		puts "Problems with file: #{exception}"
		raise
	rescue REXML::ParseException => exception
		puts "Problems with well-formedness: #{exception}"
		raise
	ensure
		vueFile.close unless vueFile.nil?
	end
	
	begin
		# if no select list is given, then all elements are selected
		@selectList = @kvProps["select"]
		puts @selectList
		@rejectList = @kvProps["reject"] || []
		puts @rejectList
		@mapHash = @kvProps["map"] || {}
		puts @mapHash
		@prefix = @kvProps["prefix"] || ""
		puts @prefix
		@postfix = @kvProps["postfix"] || ""
		puts @postfix
		@offset_x = @kvProps["offset_x"] || 16
		puts @offset_x
		@offset_y= @kvProps["offset_y"] || 16
		puts @offset_y
		@scale_x= @kvProps["scale_x"] || 1.0
		puts @scale_x
		@scale_y= @kvProps["scale_y"] || 1.0
		puts @scale_y
	rescue StandardError => exception
		puts "Problems with select, reject or map: #{exception}"
		raise
	end
end
def traverse
	child = Child.new(self, nil, nil, nil, 0.0, 0.0, 0.0, 0.0)
	REXML::XPath.each(@vueDoc, "LW-MAP/child") { |elem|
		traverseChild(elem, child)
	}
	child.putProps
	
	child.setOffset(0.0, 0.0)
	x, y, w, h = child.boundingBox
	
	@imFile.write("<div id=\"page\">\n")
	@imFile.write("<imagemap>\n")
	ext = File.extname(@vueFileName)
	puts "extension = #{ext}"
	name = @vueFileName.gsub(ext,"")
	puts "name = #{name}"
	@imFile.write("File:#{name}.jpeg|center|1100px|#{name}\n")
	child.generate(@imFile, @offset_x - x, @offset_y - y)
	@imFile.write("</imagemap>\n")
	@imFile.write("</div>\n")
end
def traverseChild(element, parent)
	type = element.attribute("xsi:type").to_s
	puts "type = #{type}"
	
	if (type != "node") and (type != "group") and (type != "link") 
		puts "no node or group or link"
		return nil
	end
	
	label = element.attribute("label").to_s
	puts "label = #{label}"
	x = element.attribute("x").to_s.to_f
	y = element.attribute("y").to_s.to_f
	w = element.attribute("width").to_s.to_f
	h = element.attribute("height").to_s.to_f
	
	child = Child.new(self, parent, type, label, x, y, w, h)
	parent.addChild(child)
	
	REXML::XPath.each(element, "child") { |elem|
		traverseChild(elem, child)
	}

	return child
end
end

class Child
	attr_reader :type
def initialize(genIm, parent, type, label, x, y, w, h)
	@genIm = genIm
	@parent = parent
	@type = type
	@label = label
	
	@x = x
	@y = y
	
	# a node in an node is resized with a factor 0.75
	if !@parent.nil? and @parent.type == "node"
		@w = w * 0.75
		@h = h * 0.75
	else
		@w = w
		@h = h
	end
	
	@x_off = 0.0
	@y_off = 0.0
	
	@children = Set.new
	puts "--- child created ---"
end
def addChild(child)
	@children.add(child)
end
def putProps
	puts "type = #{@type}, label = #{@label}, x = #{@x}, y = #{@y}, w = #{@w}, h = #{@h}"
	@children.each{ |child| child.putProps }
end
def setOffset(x_off, y_off)
	@x_off = x_off
	@y_off = y_off
	puts "x_off = #{@x_off}, y_off = #{@y_off}"
	@children.each{ |child| child.setOffset(@x_off + @x,  @y_off + @y) }
end
def generate(imFile, x_off_2, y_off_2)
	@children.each{ |child| child.generate(imFile, x_off_2, y_off_2) }
	if @type == "node"
		x = (@genIm.scale_x * (x_off_2 + @x_off +@x)).to_i
		y = (@genIm.scale_y * (y_off_2 + @y_off +@y)).to_i
		w = (@genIm.scale_x * @w).to_i
		h = (@genIm.scale_y * @h).to_i
		
		label = convert_label(@label)
		
		# Determine whether the element should be included in the imagemap.
		# if no selectList is provided (is nil), then all elements are assumed to be in the select list.
		genChild = false
		if @genIm.selectList.nil? then genChild = true end
		if !@genIm.selectList.nil? and @genIm.selectList.include?(label) then genChild = true end
		if @genIm.mapHash.key?(label) then genChild = true end
		if @genIm.rejectList.include?(label) then genChild = false end
		
		if @genIm.mapHash.key?(label)
			label_converted = @genIm.mapHash[label]
		else
			#label_converted = convert_label(@label)
			label_converted = label
		end
		label_conv = String.new(label_converted)
		
		tag_index = label_conv.index('#').nil? ? label_conv.length : label_conv.index('#')
		#puts "tag_index = #{tag_index}"
		label_conv = @genIm.prefix + label_conv.insert(tag_index, @genIm.postfix)
		
		if genChild
			#puts "rect #{x} #{y} #{x + w} #{y + h} [[#{label}]]"
			puts "  - \"#{label_conv}\""
			imFile.write("rect #{x} #{y} #{x + w} #{y + h} [[#{label_conv}]]\n")
		end
	end
end
def boundingBox
	x1 = @children.min_by{ |child| child.x1 }.x1
	y1 = @children.min_by{ |child| child.y1 }.y1
	x2 = @children.max_by{ |child| child.x2 }.x2
	y2 = @children.max_by{ |child| child.y2 }.y2
	
	puts "x1 = #{x1}, y1 = #{y1}, x2 = #{x2}, y2 = #{y2}"
	puts "w = #{x2 - x1}, h = #{y2 - y1}"
	
	return x1, y1, x2 - x1, y2 - y1
end
def x1
	@x_off + @x
end
def y1
	@y_off + @y
end
def x2
	@x_off + @x + @w
end
def y2
	@y_off + @y + @h
end
end

def convert_label(str)
	#puts "convert label string encoding = #{str.encoding}"
	str = str.gsub(/&#xa;/," ")
	str = str.gsub(/&amp;/,"&")
	str = str.gsub(/&quot;/,"\"")
	str = str.gsub(/[ _\n\t]+/," ")
	str = str.gsub(/&#xeb;/,"\xeb")
	str = str.gsub(/&#xEB;/,"\xeb")
	str = str.gsub(/&#xef;/,"\xef")
	str = str.gsub(/&#xEF;/,"\xef")
	str = str.gsub(/&#xe9;/,"\xe9")
	str = str.gsub(/&#xE9;/,"\xe9")
	str = str.gsub(/&#xf6;/,"\xf6")
	str = str.gsub(/&#xF6;/,"\xf6")
	#puts "converted str = #{str}"
	#puts "convert label string encoding = #{str.encoding}"
	return str.force_encoding("UTF-8")
end

=begin
def normalize_label(str)
	puts "normalized label string encoding = #{str.encoding}"
	str = str.gsub(/&#xa;/," ")
	str = str.gsub(/&quot;/,"\"")
	#puts "str 1 = #{str}"
	str = str.gsub(/[ _\n\t]+/," ")
	#puts "str 2 = #{str}"
	str = str.gsub(/&#xeb;/,"\xeb")
#=begin
	str = str.gsub(/&#xeb;/,"\\xEB")
	str = str.gsub(/&#xEB;/,"\\xEB")
	str = str.gsub(/&#xef;/,"\\xEF")
	str = str.gsub(/&#xEF;/,"\\xEF")
	str = str.gsub(/&#xe9;/,"\\xE9")
	str = str.gsub(/&#xE9;/,"\\xE9")
	str = str.gsub(/&#xf6;/,"\\xF6")
	str = str.gsub(/&#xF6;/,"\\xF6")
	#puts "normalized str = #{str}"
#=end
	puts "normalized label string encoding = #{str.encoding}"
	return str
end
=end

=begin
def includes_key?(map, key)
	keys = map.keys
	puts "key string encoding = #{key.encoding}"
	keys.each{ |k| puts "k string encoding = #{k.encoding}" }
	#puts keys
	#keys.each{ |k|
	#	k_a8b = String.new(k).force_encoding("ASCII-8BIT")
	#	key_a8b = key.force_encoding("ASCII-8BIT")
	#	puts "string encoding k = #{k.encoding}"
	#	puts "string encoding k_a8b = #{k_a8b.encoding}"
	#	puts "string encoding key = #{key.encoding}"
	#	puts "string encoding key_a8b = #{key_a8b.encoding}"
	#	puts "key_a8b = #{key_a8b}"
	#	puts "#{k_a8b} eql? #{key_a8b} : #{k_a8b.eql?(key_a8b)}"
	#
	ks = keys.map{ |k| String.new(k).force_encoding("ASCII-8BIT")}
	k = String.new(key).force_encoding("UTF-8")
	#return k.include?(key)
	return keys.include?(k)
end
=end

genIM = GenIM.new

=begin
k = "Interveni\xEBren: Uitvoeren van Building with Nature interventies"
puts "k = #{k}"
map = {k => "abc"}
puts map
map.each_pair{ |k, v| puts "key = #{k}" }
k2 = convert_label("Interveni&#xeb;ren: Uitvoeren van Building with Nature interventies")
puts "k2 = #{k2}"
puts "match = #{map.key?(k2)}"
=end